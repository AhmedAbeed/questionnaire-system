<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semester_course_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_course_id')->constrained('semester_courses');
            $table->foreignId('faculty_member_id')->constrained('faculty_members');
            $table->boolean('is_primary')->default(true);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['semester_course_id', 'faculty_member_id'], 'sci_course_faculty_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_course_instructors');
    }
};
