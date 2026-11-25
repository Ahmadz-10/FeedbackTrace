<?php

namespace App\Http\Requests\TaskA;

use App\Models\TaskA\FindingReview;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('finding')) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in([
                FindingReview::ACTION_APPROVE,
                FindingReview::ACTION_EDIT,
                FindingReview::ACTION_REJECT,
            ])],
            'note' => ['nullable', 'string', 'max:2000'],
            'issue_title' => ['nullable', 'string', 'max:255'],
            'issue_summary' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $isEdit = $this->input('action') === FindingReview::ACTION_EDIT;
            $hasText = filled($this->input('issue_title')) || filled($this->input('issue_summary'));

            if ($isEdit && ! $hasText) {
                $validator->errors()->add('issue_title', 'Aksi edit memerlukan issue_title dan/atau issue_summary.');
            }
            if (! $isEdit && $this->hasAny(['issue_title', 'issue_summary']) && $hasText) {
                $validator->errors()->add('action', 'issue_title/issue_summary hanya boleh dikirim pada aksi edit.');
            }
        }];
    }
}
