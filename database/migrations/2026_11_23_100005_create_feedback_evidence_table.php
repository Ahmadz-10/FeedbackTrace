<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspect_mention_id')->constrained('feedback_aspect_mentions')->cascadeOnDelete();
            $table->foreignId('feedback_record_id')->constrained('feedback_records')->cascadeOnDelete();
            $table->text('quote');
            $table->unsignedInteger('start_char')->nullable();
            $table->unsignedInteger('end_char')->nullable();
            $table->string('status', 20)->default('unverified')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_evidence');
    }
};
