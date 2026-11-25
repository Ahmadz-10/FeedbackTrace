<?php

use App\Models\TaskA\ConsolidatedFinding;
use App\Models\TaskA\FindingReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

function taReviewUrl(ConsolidatedFinding|string $finding): string
{
    return '/api/v1/task-a/findings/'.($finding instanceof ConsolidatedFinding ? $finding->finding_code : $finding).'/review';
}

class TaDenyReviewPolicy
{
    public function review(User $user, ConsolidatedFinding $finding): bool
    {
        return false;
    }
}

it('requires authentication', function () {
    $finding = taFinding();

    $this->postJson(taReviewUrl($finding), ['action' => 'approve'])->assertUnauthorized();

    expect($finding->fresh()->review_status)->toBe('pending_review')->and(FindingReview::count())->toBe(0);
});

it('has a named route under /api/v1/task-a', function () {
    expect(route('task-a.findings.review', 'F-abc', false))->toBe('/api/v1/task-a/findings/F-abc/review');
});

it('returns 404 for an unknown finding code', function () {
    $this->actingAs(User::factory()->create())->postJson(taReviewUrl('F-doesnotexist'), ['action' => 'approve'])->assertNotFound();
});

it('approves a pending finding, records who and when, and exposes it to the approved scope', function () {
    $user = User::factory()->create();
    $batch = taPersistedBatch();
    $finding = $batch->findings()->where('finding_type', 'service_weakness')->first();
    $other = $batch->findings()->where('finding_type', 'service_strength')->first();

    $response = $this->actingAs($user)->postJson(taReviewUrl($finding), ['action' => 'approve', 'note' => 'Sesuai bukti.']);

    $response->assertCreated()->assertJsonPath('data.finding_id', $finding->finding_code)
        ->assertJsonPath('data.review_status', 'approved')
        ->assertJsonPath('data.review.action', 'approve')
        ->assertJsonPath('data.review.previous_status', 'pending_review')
        ->assertJsonPath('data.review.new_status', 'approved')
        ->assertJsonPath('data.review.reviewer_id', $user->id)
        ->assertJsonPath('data.review.note', 'Sesuai bukti.');
    $finding->refresh();
    expect($finding->review_status)->toBe('approved')->and($finding->reviewed_at)->not->toBeNull()
        ->and(ConsolidatedFinding::approved()->pluck('id')->all())->toBe([$finding->id])
        ->and($other->fresh()->review_status)->toBe('pending_review');
    $review = FindingReview::sole();
    expect($review->reviewer_id)->toBe($user->id)->and($review->changes)->toBeNull();
});

it('rejects a pending finding and keeps the note', function () {
    $finding = taFinding();

    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($finding), ['action' => 'reject', 'note' => 'Bukti tidak memadai.'])
        ->assertCreated()->assertJsonPath('data.review_status', 'rejected');

    expect($finding->fresh()->review_status)->toBe('rejected')->and($finding->fresh()->reviewed_at)->not->toBeNull()
        ->and(ConsolidatedFinding::approved()->count())->toBe(0)
        ->and(FindingReview::sole()->note)->toBe('Bukti tidak memadai.');
});

it('edits title and summary, records the old/new values, and leaves the finding pending', function () {
    $finding = taFinding();
    $oldTitle = $finding->issue_title;

    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($finding), [
        'action' => 'edit', 'issue_title' => '  Antrean layanan akademik terlalu lama  ', 'issue_summary' => 'Ringkasan baru.', 'note' => 'Judul diperjelas',
    ])->assertCreated()->assertJsonPath('data.review_status', 'pending_review')->assertJsonPath('data.reviewed_at', null);

    $finding->refresh();
    expect($finding->issue_title)->toBe('Antrean layanan akademik terlalu lama')->and($finding->issue_summary)->toBe('Ringkasan baru.')
        ->and($finding->review_status)->toBe('pending_review');
    $review = FindingReview::sole();
    expect($review->action)->toBe('edit')->and($review->new_status)->toBe('pending_review')
        ->and($review->changes['issue_title'])->toBe(['old' => $oldTitle, 'new' => 'Antrean layanan akademik terlalu lama'])
        ->and($review->changes)->toHaveKeys(['issue_title', 'issue_summary']);
});

it('records only the field that actually changed', function () {
    $finding = taFinding();

    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($finding), [
        'action' => 'edit', 'issue_title' => $finding->issue_title, 'issue_summary' => 'Hanya ringkasan yang berubah.',
    ])->assertCreated();

    expect(array_keys(FindingReview::sole()->changes))->toBe(['issue_summary']);
});

it('keeps a full audit trail: edit, then approve', function () {
    $user = User::factory()->create();
    $finding = taFinding();

    $this->actingAs($user)->postJson(taReviewUrl($finding), ['action' => 'edit', 'issue_title' => 'Judul final'])->assertCreated();
    $this->actingAs($user)->postJson(taReviewUrl($finding), ['action' => 'approve'])->assertCreated();

    expect($finding->fresh()->reviews()->orderBy('id')->pluck('action')->all())->toBe(['edit', 'approve'])
        ->and($finding->fresh()->review_status)->toBe('approved')->and($finding->fresh()->issue_title)->toBe('Judul final');
});

it('treats approve and reject as final: later reviews get 409 and change nothing', function (string $second) {
    $user = User::factory()->create();
    $finding = taFinding();
    $this->actingAs($user)->postJson(taReviewUrl($finding), ['action' => 'approve'])->assertCreated();

    $this->actingAs($user)->postJson(taReviewUrl($finding), ['action' => $second, 'issue_title' => $second === 'edit' ? 'x' : null])
        ->assertStatus(409)->assertJsonPath('review_status', 'approved');

    expect($finding->fresh()->review_status)->toBe('approved')->and(FindingReview::count())->toBe(1);
})->with(['approve', 'reject', 'edit']);

it('validates the request', function (array $body, string $field) {
    $finding = taFinding();

    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($finding), $body)->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($finding->fresh()->review_status)->toBe('pending_review')->and(FindingReview::count())->toBe(0);
})->with([
    'missing action' => [[], 'action'],
    'unknown action' => [['action' => 'delete'], 'action'],
    'edit without text' => [['action' => 'edit'], 'issue_title'],
    'edit with blank text' => [['action' => 'edit', 'issue_title' => '   ', 'issue_summary' => ''], 'issue_title'],
    'title on approve' => [['action' => 'approve', 'issue_title' => 'x'], 'action'],
    'summary on reject' => [['action' => 'reject', 'issue_summary' => 'x'], 'action'],
    'title too long' => [['action' => 'edit', 'issue_title' => str_repeat('a', 256)], 'issue_title'],
    'summary too long' => [['action' => 'edit', 'issue_summary' => str_repeat('a', 2001)], 'issue_summary'],
    'note too long' => [['action' => 'approve', 'note' => str_repeat('a', 2001)], 'note'],
]);

it('rejects an edit that changes nothing', function () {
    $finding = taFinding();

    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($finding), ['action' => 'edit', 'issue_title' => $finding->issue_title])
        ->assertUnprocessable()->assertJsonValidationErrors('issue_title');

    expect(FindingReview::count())->toBe(0);
});

it('ignores attempts to set the review status through the request body', function () {
    $finding = taFinding();

    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($finding), [
        'action' => 'reject', 'review_status' => 'approved', 'reviewed_at' => '2020-01-01', 'finding_code' => 'F-hacked',
    ])->assertCreated();

    $finding->refresh();
    expect($finding->review_status)->toBe('rejected')->and($finding->finding_code)->not->toBe('F-hacked');
});

it('consults the review policy before validating and before changing anything', function () {
    Gate::policy(ConsolidatedFinding::class, TaDenyReviewPolicy::class);
    $finding = taFinding();

    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($finding), ['action' => 'approve'])->assertForbidden();
    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($finding), [])->assertForbidden();

    expect($finding->fresh()->review_status)->toBe('pending_review')->and(FindingReview::count())->toBe(0);
});

it('reviews are scoped to the requested finding', function () {
    $batch = taPersistedBatch();
    [$a, $b] = [$batch->findings()->first(), $batch->findings()->skip(1)->first()];

    $this->actingAs(User::factory()->create())->postJson(taReviewUrl($a), ['action' => 'approve'])->assertCreated();

    expect($b->fresh()->review_status)->toBe('pending_review')->and(FindingReview::where('finding_id', $b->id)->count())->toBe(0);
});
