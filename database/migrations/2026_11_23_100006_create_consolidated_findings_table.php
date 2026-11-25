<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consolidated_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('feedback_batches')->cascadeOnDelete();
            $table->string('finding_code', 100)->unique();
            $table->string('aspect_code', 80);
            $table->string('reason_category', 80);
            $table->string('finding_type', 30);
            $table->string('issue_title');
            $table->text('issue_summary');
            $table->unsignedInteger('unique_feedback_count')->default(0);
            $table->unsignedInteger('mention_count')->default(0);
            $table->json('sentiment_distribution');
            $table->json('minority_views')->nullable();
            $table->boolean('single_feedback_support')->default(false);
            $table->json('quality_flags')->nullable();
            $table->string('review_status', 30)->default('pending_review')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['batch_id', 'aspect_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consolidated_findings');
    }
};
