<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('test_cases', 'acceptance_criteria')) {
            Schema::table('test_cases', function (Blueprint $table) {
                $table->text('acceptance_criteria')
                    ->nullable()
                    ->after('user_story');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropColumn('acceptance_criteria');
        });
    }
};
