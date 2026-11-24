<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_record_id')->unique()->constrained('feedback_records')->cascadeOnDelete();
            $table->string('status', 30)->index();
            $table->string('provider', 50)->nullable();
            $table->string('model_id', 100)->nullable();
            $table->string('prompt_version', 50)->nullable();
            $table->string('pipeline_version', 50)->nullable();
            $table->string('schema_version', 20)->nullable();
            $table->unsignedInteger('processing_ms')->nullable();
            $table->json('quality_flags')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_analyses');
    }
};
