<?php

namespace App\Repositories;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionType;
use App\Contracts\QuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Exceptions\BusinessException;
use Exception;

class QuestionRepository extends BaseRepository implements QuestionRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return Question::class;
    }

    /**
     * Get questions grouped by their types
     * 
     * @return Collection The collection of questions grouped by type
     * @throws Exception When retrieval fails
     */
    public function getQuestionsByTypes(): Collection
    {
        try {
            return $this->model
                ->selectRaw('question_types.name as type, COUNT(*) as count')
                ->join('question_types', 'questions.type_id', '=', 'question_types.id')
                ->groupBy('question_types.name')
                ->get();
        } catch (Exception $e) {
            logError('Failed to get questions by types', $this->getRepositoryContext(), $e);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Group questions by their categories
     * 
     * @return Collection The collection of questions grouped by category
     * @throws Exception When retrieval fails
     */
    public function groupQuestionsByCategories(): Collection
    {
        try {
            $questions = $this->model->with('category')->get();
            return $questions->groupBy(function ($question) {
                return $question->category->name ?? 'غير مصنف';
            });
        } catch (Exception $e) {
            logError('Failed to group questions by category', $this->getRepositoryContext(), $e);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get all questions
     * 
     * @return Collection The collection of all questions
     * @throws Exception When retrieval fails
     */
    public function getQuestions(): Collection
    {
        try {
            return $this->model->all();
        } catch (Exception $e) {
            logError('Failed to get questions', $this->getRepositoryContext(), $e);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

}