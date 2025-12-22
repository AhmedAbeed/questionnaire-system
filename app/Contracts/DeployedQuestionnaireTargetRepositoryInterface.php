<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface DeployedQuestionnaireTargetRepositoryInterface extends RepositoryInterface
{
    /**
     * Find questionnaire targets by course ID
     * 
     * @param int $courseId The course ID
     * @return Collection The collection of questionnaire targets
     */
    public function findByCourse(int $courseId): Collection;
}
