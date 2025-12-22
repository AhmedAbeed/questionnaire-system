<?php

namespace App\Repositories;

use App\Contracts\QuestionCategoryRepositoryInterface;
use App\Models\QuestionCategory;

class QuestionCategoryRepository extends BaseRepository implements QuestionCategoryRepositoryInterface
{
    public function model(): string
    {
        return QuestionCategory::class;
    }
    // Add any QuestionCategory-specific repository methods here
} 