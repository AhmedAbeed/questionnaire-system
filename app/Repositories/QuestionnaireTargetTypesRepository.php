<?php

namespace App\Repositories;

use App\Models\QuestionnaireTargetType;
use App\Contracts\QuestionnaireTargetTypesInterface;

class QuestionnaireTargetTypesRepository extends BaseRepository implements QuestionnaireTargetTypesInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return QuestionnaireTargetType::class;
    }
}
