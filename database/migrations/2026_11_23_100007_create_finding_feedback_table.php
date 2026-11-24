<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained('consolidated_findings')->cascadeOnDelete();
            $table->foreignId('feedback_record_id')->constrained('feedback_records')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['finding_id', 'feedback_record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_feedback');
    }
};
