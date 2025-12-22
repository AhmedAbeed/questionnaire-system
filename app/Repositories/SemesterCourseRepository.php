<?php

namespace App\Repositories;

use App\Contracts\SemesterCourseRepositoryInterface;
use App\Models\SemesterCourse;
use Exception;

class SemesterCourseRepository extends BaseRepository implements SemesterCourseRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return SemesterCourse::class;
    }

    /**
     * Find semester course by course and semester IDs
     * 
     * @param int $courseId The course ID
     * @param int $semesterId The semester ID
     * @return SemesterCourse|null The semester course or null if not found
     * @throws Exception When retrieval fails
     */
    public function findByCourseAndSemester(int $courseId, int $semesterId): ?SemesterCourse
    {
        try {
            return $this->model
                ->where('course_id', $courseId)
                ->where('semester_id', $semesterId)
                ->first();
        } catch (Exception $e) {
            logError('Failed to find semester course by course and semester', $this->getRepositoryContext(), $e, [
                'course_id' => $courseId,
                'semester_id' => $semesterId
            ]);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Find semester course by course ID
     * 
     * @param int $courseId The course ID
     * @return SemesterCourse|null The semester course or null if not found
     * @throws Exception When retrieval fails
     */
    public function findByCourse(int $courseId): ?SemesterCourse
    {
        try {
            return $this->model
                ->where('course_id', $courseId)
                ->first();
        } catch (Exception $e) {
            logError('Failed to find semester course by course', $this->getRepositoryContext(), $e, ['course_id' => $courseId]);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
} 