<?php

namespace App\Repositories;

use App\Contracts\QuestionTypeRepositoryInterface;
use App\Models\QuestionType;

class QuestionTypeRepository extends BaseRepository implements QuestionTypeRepositoryInterface
{
    public function model(): string
    {
        return QuestionType::class;
    }
    // Add any QuestionType-specific repository methods here
} 