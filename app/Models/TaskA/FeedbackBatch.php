<?php

namespace App\Models\TaskA;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackBatch extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIALLY_COMPLETED = 'partially_completed';
    public const STATUS_FAILED = 'failed';

    protected $table = 'feedback_batches';
    protected $fillable = [
        'batch_code', 'title', 'source', 'schema_version', 'status', 'options',
        'ai_request_id', 'ai_job_id', 'model_metadata', 'stats', 'error_message',
        'started_at', 'finished_at', 'created_by',
    ];
    protected $casts = [
        'options' => 'array',
        'model_metadata' => 'array',
        'stats' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(FeedbackRecord::class, 'batch_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(ConsolidatedFinding::class, 'batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
