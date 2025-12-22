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
        Schema::create('questionnaire_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('deployed_questionnaires')->onDelete('cascade');
            $table->json('analysis_data');
            $table->timestamp('generated_at');
            $table->string('version')->default('1.0');
            $table->string('status')->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Add index for faster lookups
            $table->index(['questionnaire_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaire_analyses');
    }
}; 