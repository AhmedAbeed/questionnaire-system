<?php

namespace App\Repositories;

use App\Models\DeployedQuestionOption;
use App\Contracts\DeployedQuestionOptionRepositoryInterface;

class DeployedQuestionOptionRepository extends BaseRepository implements DeployedQuestionOptionRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return DeployedQuestionOption::class;
    }
}