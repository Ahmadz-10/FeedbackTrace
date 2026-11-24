<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 100)->unique();
            $table->string('title')->nullable();
            $table->string('source', 100)->nullable();
            $table->string('schema_version', 20)->default('1.0');
            $table->string('status', 30)->default('pending')->index();
            $table->json('options')->nullable();
            $table->string('ai_request_id', 100)->nullable();
            $table->string('ai_job_id', 100)->nullable()->index();
            $table->json('model_metadata')->nullable();
            $table->json('stats')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_batches');
    }
};
