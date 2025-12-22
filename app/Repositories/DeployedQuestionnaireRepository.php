<?php

namespace App\Repositories;

use App\Models\DeployedQuestionnaire;
use App\Contracts\DeployedQuestionnaireRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Response;
use App\Models\Faculty;
use App\Models\SemesterCourse;
use App\Models\QuestionResponse;
use App\Models\Semester;
use App\Models\Course;
use App\Models\QuestionnaireTargetType;
use Exception;
use App\Exceptions\BusinessException;

class DeployedQuestionnaireRepository extends BaseRepository implements DeployedQuestionnaireRepositoryInterface
{
    /**
     * Get the model class name.
     *
     * @return string
     */
    public function model(): string
    {
        return DeployedQuestionnaire::class;
    }

    /**
     * Calculate the completion rate for a questionnaire.
     *
     * @param int $questionnaireId
     * @return float
     * @throws Exception
     */
    public function completionRate($questionnaireId): float
    {
        try {
            $questionnaire = $this->model->find($questionnaireId);
            $responseCount = $questionnaire->responses()->withoutGlobalScopes()->count();
            $eligibleRespondentsCount = $questionnaire->getEligibleRespondentsCount();

            $completionRate = $eligibleRespondentsCount > 0 ? round(($responseCount / $eligibleRespondentsCount) * 100, 2) : 0;
            return $completionRate;
        } catch (Exception $e) {
            logError('Failed to calculate completion rate', 'DeployedQuestionnaireRepository', $e);
            throw $e;
        }
    }

    /**
     * Get active questionnaires with formatted data.
     *
     * @return Collection
     * @throws Exception
     */
    public function getActiveQuestionnaires(): Collection
    {
        try {
            $activQs = $this->model->with(['targetType', 'targets.semesterCourse.course.faculty'])
                ->where('status', 'active')
                ->where('open_date', '<=', now())
                ->where('close_date', '>=', now())
                ->get();
            return $activQs;
        } catch (Exception $e) {
            logError('Failed to retrieve active questionnaires', 'DeployedQuestionnaireRepository', $e);
            throw $e;
        }
    }

    /**
     * Get questionnaires eligible for the authenticated respondent.
     *
     * @return Collection
     * @throws Exception
     */
    public function getRespondentEligibleQuestionnaires(): Collection
    {
        try {
            $respondent = auth()->user()->load('student.program.faculty', 'student.enrollments.semesterCourse');
            $facultyId = $respondent->student?->program?->faculty?->id;
            $programId = $respondent->student?->program?->id;
            $semesterCourseIds = $respondent->student?->enrollments
                ->pluck('semesterCourse.id')
                ->filter()
                ->all();

            $eligibleQs = $this->model->with('targets.faculty', 'targets.program', 'targets.semesterCourse')
                ->where('status', 'active')
                ->where('open_date', '<=', now())
                ->where('close_date', '>=', now())
                ->whereHas('targets', function ($query) use ($facultyId, $programId, $semesterCourseIds) {
                    $query->where(function ($q) use ($facultyId, $programId, $semesterCourseIds) {
                        if ($facultyId) {
                            $q->where('faculty_id', $facultyId);
                        }
                        if ($programId) {
                            $q->orWhere('program_id', $programId);
                        }
                        if (!empty($semesterCourseIds)) {
                            $q->orWhereIn('semester_course_id', $semesterCourseIds);
                        }
                    });
                })
                ->whereDoesntHave('responses', function ($query) use ($respondent) {
                    $query->where('user_id', $respondent->id);
                })
                ->get();
            return $eligibleQs;
        } catch (Exception $e) {
            logError('Failed to retrieve respondent-eligible questionnaires', 'DeployedQuestionnaireRepository', $e);
            throw $e;
        }
    }

    /**
     * Get total number of responses for all active questionnaires.
     *
     * @return int
     * @throws Exception
     */
    public function totalResponses(): int
    {
        try {
            $totalResponses = $this->model
                ->where('status', 'active')
                ->withCount('responses')
                ->get()
                ->sum('responses_count');
            return $totalResponses;
        } catch (Exception $e) {
            logError('Failed to get total responses', 'DeployedQuestionnaireRepository', $e);
            throw $e;
        }
    }

    /**
     * Get total number of eligible respondents for questionnaires.
     *
     * @return int
     */
    public function totalEligibleRespondents(): int
    {
        try {
            $totalEligibleRespondents = $this->model
                ->where('status', 'active')
                ->get()
                ->sum(fn($questionnaire) => $questionnaire->getEligibleRespondentsCount());
            return $totalEligibleRespondents;
        } catch (Exception $e) {
            logError('Failed to get total eligible respondents', 'DeployedQuestionnaireRepository', $e);
            throw $e;
        }
    }

    /**
     * Create a new questionnaire.
     *
     * @param array $data
     * @return DeployedQuestionnaire
     * @throws Exception
     */
    private function createQuestionnaire(array $data): DeployedQuestionnaire
    {
        try {
            $questionnaire = $this->model->create([
                'template_id' => $data['template_id'],
                'name' => $data['name'],
                'target_type_id' => $data['target_type_id'],
                'open_date' => $data['open_date'],
                'close_date' => $data['close_date'],
                'creator_id' => auth()->id() ?? 1,
                'status' => $data['status'] ?? 'active',
            ]);
            return $questionnaire;
        } catch (Exception $e) {
            logError('Failed to create questionnaire', 'DeployedQuestionnaireRepository', $e);
            throw $e;
        }
    }

    /**
     * Get statistics for a deployed questionnaire by ID.
     *
     * @param int $deployedQuestionnaireId
     * @return DeployedQuestionnaire|null
     * @throws Exception
     */
    public function getQuestionnaireStatsById(int $deployedQuestionnaireId): ?DeployedQuestionnaire
    {
        try {
            $deployedQuestionnaire = $this->find($deployedQuestionnaireId);
            $deployedQuestionnaire->response_count = $deployedQuestionnaire->responses()->count();
            $deployedQuestionnaire->eligible_respondents_count = $deployedQuestionnaire->getEligibleRespondentsCount();
            $deployedQuestionnaire->complete_rate = $deployedQuestionnaire->eligible_respondents_count > 0
                ? round(($deployedQuestionnaire->response_count / $deployedQuestionnaire->eligible_respondents_count) * 100, 1)
                : 0;
            return $deployedQuestionnaire;
        } catch (Exception $e) {
            logError('Failed to get deployed questionnaire result', 'DeployedQuestionnaireRepository', $e);
            throw $e;
        }
    }

}