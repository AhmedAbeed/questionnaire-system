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
        $tables = [
            'academic_levels',
            'courses',
            'deployed_questions',
            'deployed_questionnaires',
            'deployed_question_options',
            'faculties',
            'faculty_members',
            'import_progress',
            'lectures',
            'programs',
            'questions',
            'question_categories',
            'question_options',
            'question_responses',
            'question_types',
            'questionnaire_targets',
            'questionnaire_target_types',
            'questionnaire_templates',
            'responses',
            'semesters',
            'semester_courses',
            'semester_course_instructors',
            'students',
            'template_questions',
            'users',
            'external_respondents',
            'enrollments'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                if (!Schema::hasColumn($tableName, 'deleted_at')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->softDeletes();
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'academic_levels',
            'courses',
            'deployed_questions',
            'deployed_questionnaires',
            'deployed_question_options',
            'faculties',
            'faculty_members',
            'import_progress',
            'lectures',
            'programs',
            'questions',
            'question_categories',
            'question_options',
            'question_responses',
            'question_types',
            'questionnaire_targets',
            'questionnaire_target_types',
            'questionnaire_templates',
            'responses',
            'semesters',
            'semester_courses',
            'semester_course_instructors',
            'students',
            'template_questions',
            'users',
            'external_respondents',
            'enrollments'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropSoftDeletes();
                    });
                }
            }
        }
    }
};
