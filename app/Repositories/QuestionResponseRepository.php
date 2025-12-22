<?php

namespace App\Repositories;

use App\Models\QuestionResponse;
use App\Contracts\ResponseRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Models\DeployedQuestionnaire;
use App\Models\Question;
use App\Exceptions\BusinessException;
use App\Models\DeployedQuestion;
use App\Models\Student;
use App\Models\QuestionnaireTarget;
use Exception;
use App\Contracts\QuestionResponseRepositoryInterface;

class QuestionResponseRepository extends BaseRepository implements QuestionResponseRepositoryInterface
{

    /**
     * Get the model class name.
     *
     * @return string
     */
    public function model(): string
    {
        return QuestionResponse::class;
    }

    public function getByQuestionnaireAndQuestion($questionnaireId, $questionId)
    {
        return $this->model->whereHas('response', function ($query) use ($questionnaireId) {
            $query->where('questionnaire_id', $questionnaireId);
        })->where('question_id', $questionId)->get();
    }
}