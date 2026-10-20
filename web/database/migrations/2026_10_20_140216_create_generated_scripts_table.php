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
        Schema::create('generated_scripts', function (Blueprint $table) {
            $table->id('generated_script_id');
            $table->foreignId('llm_output_id')
                ->unique()
                ->constrained('llm_outputs', 'llm_output_id')
                ->cascadeOnDelete();
            $table->longText('content'); // Selenium script
            $table->string('status', 20); // completed | partial | failed
            $table->text('unresolved_step')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_scripts');
    }
};
