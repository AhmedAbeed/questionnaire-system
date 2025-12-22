<?php

namespace App\Repositories;

use App\Models\QuestionnaireTarget;
use App\Contracts\DeployedQuestionnaireTargetRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class DeployedQuestionnaireTargetRepository extends BaseRepository implements DeployedQuestionnaireTargetRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return QuestionnaireTarget::class;
    }

    /**
     * Find questionnaire targets by course ID
     * 
     * @param int $courseId The course ID
     * @return Collection The collection of questionnaire targets
     * @throws Exception When retrieval fails
     */
    public function findByCourse(int $courseId): Collection
    {
        try {
            return $this->model
                ->whereHas('semesterCourse', fn($q) => $q->where('course_id', $courseId))
                ->get();
        } catch (Exception $e) {
            logError('Failed to find questionnaire targets by course', $this->getRepositoryContext(), $e, ['course_id' => $courseId]);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}