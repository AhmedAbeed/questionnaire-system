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
        Schema::create('questionnaire_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('deployed_questionnaires')->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->cascadeOnDelete();
            $table->foreignId('semester_course_id')->nullable()->constrained('semester_courses')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaire_targets');
    }
};
