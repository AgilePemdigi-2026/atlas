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
        Schema::table('auth_profiles', function (Blueprint $table) {
            $table->text('submit_selector')->nullable()->after('login_url');
            $table->text('success_indicator')->nullable()->after('submit_selector');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_profiles', function (Blueprint $table) {
            $table->dropColumn(['submit_selector', 'success_indicator']);
        });
    }
};
