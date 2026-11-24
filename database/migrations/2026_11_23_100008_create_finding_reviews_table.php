<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finding_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->constrained('consolidated_findings')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->string('previous_status', 30);
            $table->string('new_status', 30);
            $table->json('changes')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['finding_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finding_reviews');
    }
};
