<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('feedback_batches')->cascadeOnDelete();
            $table->string('feedback_id', 100);
            $table->string('question_id', 100)->index();
            $table->text('comment_text');
            $table->text('normalized_text')->nullable();
            $table->string('language_hint', 20)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->json('quality_flags')->nullable();
            $table->timestamps();
            $table->unique(['batch_id', 'feedback_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_records');
    }
};
