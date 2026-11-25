<?php

namespace App\Models\TaskA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackAspectMention extends Model
{
    protected $table = 'feedback_aspect_mentions';
    protected $fillable = [
        'feedback_analysis_id', 'feedback_record_id', 'mention_code', 'aspect_code',
        'is_proposed_aspect', 'proposed_aspect_label', 'sentiment', 'sentiment_strength',
        'reason_type', 'reason_category', 'reason_description',
    ];
    protected $casts = ['is_proposed_aspect' => 'boolean'];

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(FeedbackAnalysis::class, 'feedback_analysis_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(FeedbackRecord::class, 'feedback_record_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(FeedbackEvidence::class, 'aspect_mention_id');
    }
}
