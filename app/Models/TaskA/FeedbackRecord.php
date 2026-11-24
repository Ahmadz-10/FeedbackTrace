<?php

namespace App\Models\TaskA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FeedbackRecord extends Model
{
    protected $table = 'feedback_records';
    // comment_text is fillable for initial import; do not change it after import.
    protected $fillable = [
        'batch_id', 'feedback_id', 'question_id', 'comment_text', 'normalized_text',
        'language_hint', 'submitted_at', 'quality_flags',
    ];
    protected $casts = [
        'submitted_at' => 'datetime',
        'quality_flags' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FeedbackBatch::class, 'batch_id');
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(FeedbackAnalysis::class, 'feedback_record_id');
    }

    public function aspectMentions(): HasMany
    {
        return $this->hasMany(FeedbackAspectMention::class, 'feedback_record_id');
    }

    public function findings(): BelongsToMany
    {
        return $this->belongsToMany(
            ConsolidatedFinding::class,
            'finding_feedback',
            'feedback_record_id',
            'finding_id'
        )->withTimestamps();
    }
}
