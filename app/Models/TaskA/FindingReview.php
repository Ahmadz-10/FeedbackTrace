<?php

namespace App\Models\TaskA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingReview extends Model
{
    public const ACTION_APPROVE = 'approve';
    public const ACTION_EDIT = 'edit';
    public const ACTION_REJECT = 'reject';

    protected $table = 'finding_reviews';
    protected $fillable = [
        'finding_id', 'reviewer_id', 'action', 'previous_status', 'new_status', 'changes', 'note',
    ];
    protected $casts = ['changes' => 'array'];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(ConsolidatedFinding::class, 'finding_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewer_id');
    }
}
