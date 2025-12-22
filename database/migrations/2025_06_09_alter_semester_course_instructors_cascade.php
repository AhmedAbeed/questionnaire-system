<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing foreign key constraints if they exist
        Schema::table('semester_course_instructors', function (Blueprint $table) {
            $foreignKeys = $this->getForeignKeys('semester_course_instructors');

            // Drop faculty_member_id foreign key if it exists
            if (in_array('semester_course_instructors_faculty_member_id_foreign', $foreignKeys)) {
                $table->dropForeign('semester_course_instructors_faculty_member_id_foreign');
            }

            // Drop semester_course_id foreign key if it exists (in case it was created with a different name)
            $semesterCourseForeignKey = $this->findForeignKeyForColumn('semester_course_instructors', 'semester_course_id');
            if ($semesterCourseForeignKey) {
                $table->dropForeign($semesterCourseForeignKey);
            }
        });

        // Add foreign key constraints with ON DELETE CASCADE
        Schema::table('semester_course_instructors', function (Blueprint $table) {
            $table->foreign('faculty_member_id')
                  ->references('id')
                  ->on('faculty_members')
                  ->onDelete('cascade');
            $table->foreign('semester_course_id')
                  ->references('id')
                  ->on('semester_courses')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Drop the modified foreign key constraints
        Schema::table('semester_course_instructors', function (Blueprint $table) {
            $table->dropForeign(['faculty_member_id']);
            $table->dropForeign(['semester_course_id']);
        });

        // Re-add the original foreign key constraints without CASCADE
        Schema::table('semester_course_instructors', function (Blueprint $table) {
            $table->foreign('faculty_member_id')->references('id')->on('faculty_members');
            $table->foreign('semester_course_id')->references('id')->on('semester_courses');
        });
    }

    /**
     * Get all foreign key constraint names for a table.
     *
     * @param string $table
     * @return array
     */
    private function getForeignKeys(string $table): array
    {
        $foreignKeys = [];
        $result = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = ? AND CONSTRAINT_NAME LIKE '%_foreign'
        ", [$table]);

        foreach ($result as $row) {
            $foreignKeys[] = $row->CONSTRAINT_NAME;
        }

        return $foreignKeys;
    }

    /**
     * Find the foreign key constraint name for a specific column in a table.
     *
     * @param string $table
     * @param string $column
     * @return string|null
     */
    private function findForeignKeyForColumn(string $table, string $column): ?string
    {
        $result = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND CONSTRAINT_NAME LIKE '%_foreign'
        ", [$table, $column]);

        return $result ? $result->CONSTRAINT_NAME : null;
    }
};