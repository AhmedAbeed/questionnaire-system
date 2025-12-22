<?php

namespace App\Contracts;

use App\Models\Response;
use Illuminate\Database\Eloquent\Collection;

interface ResponseRepositoryInterface extends RepositoryInterface
{
    /**
     * Get weekly response data for charts
     * 
     * @return array The weekly data with labels and counts
     */
    public function getWeeklyData(): array;

    /**
     * Get monthly response data for charts
     * 
     * @return array The monthly data with labels and counts
     */
    public function getMonthlyData(): array;

    /**
     * Get yearly response data for charts
     * 
     * @return array The yearly data with labels and counts
     */
    public function getYearlyData(): array;

    /**
     * Create a questionnaire response with question responses
     * 
     * @param array $response The response data
     * @return Response The created response
     */
    public function createResponse(array $response): Response;

    /**
     * Get response data by timeframe for dashboard
     * 
     * @return array The response data organized by timeframe
     */
    public function getResponseDataByTimeframe(): array;

    /**
     * Get responses and questions by questionnaire ID
     * 
     * @param int $questionnaireId The questionnaire ID
     * @return array The responses and questions data
     */
    public function getResponsesQuestionsByQuestionnaire(int $questionnaireId): array;

    /**
     * Delete responses by student and semester course
     * 
     * @param int $studentId The student ID
     * @param int $semesterCourseId The semester course ID
     * @return bool True if deletion was successful
     */
    public function deleteResponsesByStudentAndSemesterCourse(int $studentId, int $semesterCourseId): bool;
}   