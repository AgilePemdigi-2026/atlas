<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->text('user_story')->nullable()->change();
            $table->text('acceptance_criteria')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep fields nullable to avoid losing existing draft data.
        // Restoring NOT NULL requires ensuring no NULL values exist.
    }
};