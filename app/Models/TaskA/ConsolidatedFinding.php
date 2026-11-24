<?php

namespace App\Models\TaskA;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsolidatedFinding extends Model
{
    public const REVIEW_PENDING = 'pending_review';
    public const REVIEW_APPROVED = 'approved';
    public const REVIEW_REJECTED = 'rejected';

    protected $table = 'consolidated_findings';
    // review_status and reviewed_at are intentionally excluded from mass assignment.
    protected $fillable = [
        'batch_id', 'finding_code', 'aspect_code', 'reason_category', 'finding_type',
        'issue_title', 'issue_summary', 'unique_feedback_count', 'mention_count',
        'sentiment_distribution', 'minority_views', 'single_feedback_support', 'quality_flags',
    ];
    protected $casts = [
        'unique_feedback_count' => 'integer',
        'mention_count' => 'integer',
        'sentiment_distribution' => 'array',
        'minority_views' => 'array',
        'single_feedback_support' => 'boolean',
        'quality_flags' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('review_status', self::REVIEW_APPROVED);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FeedbackBatch::class, 'batch_id');
    }

    public function feedbackRecords(): BelongsToMany
    {
        return $this->belongsToMany(
            FeedbackRecord::class,
            'finding_feedback',
            'finding_id',
            'feedback_record_id'
        )->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FindingReview::class, 'finding_id');
    }
}
