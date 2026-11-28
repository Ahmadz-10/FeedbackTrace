<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consolidated_findings', function (Blueprint $table) {
            $table->string('proposed_aspect_label', 80)->nullable()->after('aspect_code');
        });
    }

    public function down(): void
    {
        Schema::table('consolidated_findings', function (Blueprint $table) {
            $table->dropColumn('proposed_aspect_label');
        });
    }
};
