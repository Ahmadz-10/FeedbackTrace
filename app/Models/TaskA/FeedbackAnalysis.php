<?php

namespace App\Models\TaskA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackAnalysis extends Model
{
    public const STATUS_ANALYZED = 'analyzed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    protected $table = 'feedback_analyses';
    protected $fillable = [
        'feedback_record_id', 'status', 'provider', 'model_id', 'prompt_version',
        'pipeline_version', 'schema_version', 'processing_ms', 'quality_flags', 'error_message',
    ];
    protected $casts = [
        'processing_ms' => 'integer',
        'quality_flags' => 'array',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(FeedbackRecord::class, 'feedback_record_id');
    }

    public function aspectMentions(): HasMany
    {
        return $this->hasMany(FeedbackAspectMention::class, 'feedback_analysis_id');
    }
}
