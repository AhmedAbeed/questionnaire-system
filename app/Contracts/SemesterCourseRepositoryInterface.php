<?php

namespace App\Contracts;

use App\Models\SemesterCourse;

interface SemesterCourseRepositoryInterface extends RepositoryInterface
{
    /**
     * Find semester course by course and semester IDs
     * 
     * @param int $courseId The course ID
     * @param int $semesterId The semester ID
     * @return SemesterCourse|null The semester course or null if not found
     */
    public function findByCourseAndSemester(int $courseId, int $semesterId): ?SemesterCourse;

    /**
     * Find semester course by course ID
     * 
     * @param int $courseId The course ID
     * @return SemesterCourse|null The semester course or null if not found
     */
    public function findByCourse(int $courseId): ?SemesterCourse;
} 