<?php

namespace App\Models\TaskA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackEvidence extends Model
{
    public const STATUS_UNVERIFIED = 'unverified';
    public const STATUS_VALID = 'valid';
    public const STATUS_INVALID = 'invalid';

    protected $table = 'feedback_evidence';
    protected $fillable = [
        'aspect_mention_id', 'feedback_record_id', 'quote', 'start_char', 'end_char', 'status',
    ];
    protected $casts = [
        'start_char' => 'integer',
        'end_char' => 'integer',
    ];

    public function aspectMention(): BelongsTo
    {
        return $this->belongsTo(FeedbackAspectMention::class, 'aspect_mention_id');
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(FeedbackRecord::class, 'feedback_record_id');
    }
}
