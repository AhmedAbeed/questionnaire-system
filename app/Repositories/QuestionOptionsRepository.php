<?php

namespace App\Repositories;

use App\Models\QuestionOption;
use App\Contracts\QuestionOptionsRepositoryInterface;

class QuestionOptionsRepository extends BaseRepository implements QuestionOptionsRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return QuestionOption::class;
    }

}