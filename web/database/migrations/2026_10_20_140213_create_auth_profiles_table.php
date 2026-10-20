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
        Schema::create('auth_profiles', function (Blueprint $table) {
            $table->id('auth_profile_id');
            $table->foreignId('test_case_id')
                ->unique()
                ->constrained('test_cases', 'test_case_id')
                ->cascadeOnDelete();
            // JSON
            $table->text('credentials');
            $table->text('login_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_profiles');
    }
};
