<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_aspect_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_analysis_id')->constrained('feedback_analyses')->cascadeOnDelete();
            $table->foreignId('feedback_record_id')->constrained('feedback_records')->cascadeOnDelete();
            $table->string('mention_code', 100)->nullable();
            $table->string('aspect_code', 80);
            $table->boolean('is_proposed_aspect')->default(false);
            $table->string('proposed_aspect_label')->nullable();
            $table->string('sentiment', 20);
            $table->string('sentiment_strength', 20)->nullable();
            $table->string('reason_type', 40);
            $table->string('reason_category', 80);
            $table->text('reason_description')->nullable();
            $table->timestamps();
            $table->index(['aspect_code', 'sentiment']);
            $table->index('reason_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_aspect_mentions');
    }
};
