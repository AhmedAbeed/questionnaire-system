<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface QuestionRepositoryInterface extends RepositoryInterface
{
    /**
     * Get questions grouped by their types
     * 
     * @return Collection The collection of questions grouped by type
     */
    public function getQuestionsByTypes(): Collection;

    /**
     * Group questions by their categories
     * 
     * @return Collection The collection of questions grouped by category
     */
    public function groupQuestionsByCategories(): Collection;

    /**
     * Get all questions
     * 
     * @return Collection The collection of all questions
     */
    public function getQuestions(): Collection;
}

