<?php

namespace App\Services;

use App\Services\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class QuestionnaireTemplateService extends BaseService
{

    public function createTemplate(array $data)
    {
        try {
            $this->unitOfWork->beginTransaction();
            // Create base template
            $template = $this->createBaseTemplate($data);
            // Create template questions
            $this->createTemplateQuestions($template, $data['questions']);
            
            $this->unitOfWork->endTransaction();
            return $template;
            
        } catch (BusinessValidationException $e) {
            $this->unitOfWork->rollback();
            logError('Failed to create questionnaire template', 'QuestionnaireTemplateService', $e);
            throw $e;
        } catch (Exception $e) {
            $this->unitOfWork->rollback();
            logError('Failed to create questionnaire template', 'QuestionnaireTemplateService', $e);
            throw new ServiceException('Unable to create questionnaire template due to system error', 0, $e);
        }
    }

    private function createBaseTemplate(array $data)
    {
        return $this->unitOfWork->questionnaireTemplates()->create([
            'name' => $data['name'],
            'description' => $data['description'],
            'is_active' => (int) $data['is_active'],
        ]);
    }

    private function createTemplateQuestions($template, array $questions)
    {
        foreach ($questions as $question) {
            $template->templateQuestions()->create([
                'question_id' => $question['id'],
                'order' => $question['order'],
                'is_required' => (int) $question['is_required'],
            ]);
        }
    }

    public function getActiveQuestionnaireTemplates()
    {
        try {
            return $this->unitOfWork->questionnaireTemplates()->getActiveQuestionnaireTemplates();
        } catch (BusinessValidationException $e) {
            logError('Failed to get active questionnaire templates', 'QuestionnaireTemplateService', $e);
            throw $e;
        } catch (Exception $e) {
            logError('Failed to get active questionnaire templates', 'QuestionnaireTemplateService', $e);
            throw new ServiceException('Unable to retrieve active questionnaire templates due to system error', 0, $e);
        }
    }

    public function getQuestionnaireTemplate($id)
    {
        try {
            return $this->unitOfWork->questionnaireTemplates()->getQuestionnaireTemplate($id);
        } catch (BusinessValidationException $e) {
            logError('Failed to get questionnaire template', 'QuestionnaireTemplateService', $e);
            throw $e;
        } catch (Exception $e) {
            logError('Failed to get questionnaire template', 'QuestionnaireTemplateService', $e);
            throw new ServiceException('Unable to retrieve questionnaire template due to system error', 0, $e);
        }
    }


    /**
     * Get questionnaire template statistics for dashboard cards.
     *
     * @throws ServiceException
     */
    public function getStats(): array
    {
        try {
            $repo = $this->unitOfWork->questionnaireTemplates();
            return [
                'total_questionnaire_templates' => [
                    'value' => formatNumber($repo->count(), 0),
                    'updated' => formatDate($repo->latestUpdateTime()),
                ]
            ];
        } catch (Exception $e) {
            logError('Failed to fetch questionnaire template statistics', 'QuestionnaireTemplateService', $e);
            throw new ServiceException('Unable to retrieve questionnaire template statistics due to system error', 0, $e);
        }
    }

    /**
     * Get DataTable for questionnaire templates.
     *
     * @throws ServiceException
     */
    public function getDataTable()
    {
        try {
            $query = $this->unitOfWork->questionnaireTemplates()->query()->with('deployedQuestionnaires');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('status', fn($template) => $template->is_active ? __('Active') : __('Inactive'))
                ->addColumn('count', fn($template) => $template->deployedQuestionnaires->count())
                ->addColumn('created_at', fn($template) => formatDate($template->created_at))
                ->addColumn('actions', fn($template) => $this->getActionButtons($template))
                ->rawColumns(['actions'])
                ->make(true);
        } catch (Exception $e) {
            logError('Failed to generate datatable', 'QuestionnaireTemplateService', $e);
            throw new ServiceException('Unable to load questionnaire template data due to system error', 0, $e);
        }
    }

    /**
     * Generate HTML action buttons for questionnaire template.
     *
     * @param $template
     * @return string
     */
    private function getActionButtons($template): string
    {
        $url = route('questionnaire.template.show', ['id' => $template->id]);
        return '<div class="btn-group">'
            . '<a href="' . $url . '" class="btn btn-outline-info rounded ms-2" target="_blank" title="' . __('view') . '">' .
            '<i class="fa fa-eye"></i>' . __('عرض') . '</a>'
            . '</div>';
    }

}