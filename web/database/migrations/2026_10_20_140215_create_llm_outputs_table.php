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
        Schema::create('llm_outputs', function (Blueprint $table) {
            $table->id('llm_output_id');
            $table->foreignId('test_case_id')
                ->unique()
                ->constrained('test_cases', 'test_case_id')
                ->cascadeOnDelete();
            // json
            $table->longText('output');
            $table->string('validation_status', 10)->default('pending'); // pending | valid | invalid
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_outputs');
    }
};
