<?php

namespace App\Http\Controllers\TaskA;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskA\ReviewFindingRequest;
use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FindingReview;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function store(ReviewFindingRequest $request, ConsolidatedFinding $finding): JsonResponse
    {
        [$finding, $review] = DB::transaction(function () use ($request, $finding) {
            $locked = ConsolidatedFinding::query()
                ->whereKey($finding->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->review_status !== ConsolidatedFinding::REVIEW_PENDING) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Temuan ini sudah final dan tidak dapat ditinjau lagi.',
                    'review_status' => $locked->review_status,
                ], 409));
            }

            $action = $request->validated('action');
            $previous = $locked->review_status;
            $changes = null;

            if ($action === FindingReview::ACTION_EDIT) {
                $changes = $this->diff($locked, $request->safe()->only(['issue_title', 'issue_summary']));
                if ($changes === []) {
                    throw ValidationException::withMessages([
                        'issue_title' => 'Tidak ada perubahan dibanding nilai saat ini.',
                    ]);
                }
                $locked->fill(array_map(fn ($change) => $change['new'], $changes));
                $new = ConsolidatedFinding::REVIEW_PENDING;
            } else {
                $new = $action === FindingReview::ACTION_APPROVE
                    ? ConsolidatedFinding::REVIEW_APPROVED
                    : ConsolidatedFinding::REVIEW_REJECTED;
                $locked->forceFill(['review_status' => $new, 'reviewed_at' => now()]);
            }

            $locked->save();
            $review = $locked->reviews()->create([
                'reviewer_id' => $request->user()->getKey(),
                'action' => $action,
                'previous_status' => $previous,
                'new_status' => $new,
                'changes' => $changes,
                'note' => $request->validated('note'),
            ]);

            return [$locked, $review];
        });

        return response()->json(['data' => [
            'finding_id' => $finding->finding_code,
            'review_status' => $finding->review_status,
            'issue_title' => $finding->issue_title,
            'issue_summary' => $finding->issue_summary,
            'reviewed_at' => $finding->reviewed_at?->toIso8601String(),
            'review' => [
                'id' => $review->id,
                'action' => $review->action,
                'previous_status' => $review->previous_status,
                'new_status' => $review->new_status,
                'changes' => $review->changes,
                'note' => $review->note,
                'reviewer_id' => $review->reviewer_id,
                'created_at' => $review->created_at->toIso8601String(),
            ],
        ]], 201);
    }

    private function diff(ConsolidatedFinding $finding, array $input): array
    {
        $changes = [];
        foreach (['issue_title', 'issue_summary'] as $field) {
            $new = trim((string) ($input[$field] ?? ''));
            if ($new !== '' && $new !== $finding->{$field}) {
                $changes[$field] = ['old' => $finding->{$field}, 'new' => $new];
            }
        }
        return $changes;
    }
}
