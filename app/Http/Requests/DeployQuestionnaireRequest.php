<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class DeployQuestionnaireRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow the request
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'questionnaire.template.type' => ['required', 'in:template,new'],
            'questionnaire.template.id' => ['required_if:questionnaire.template.type,template', 'nullable', 'string'],

            'questionnaire.target.type' => ['required', 'string'],
            'questionnaire.target.scope' => ['nullable', 'string'],
            'questionnaire.target.role' => ['nullable', 'string'],

            'questionnaire.settings.name' => ['required', 'string'],
            'questionnaire.settings.status' => ['required', Rule::in(['draft', 'active', 'closed'])],
            'questionnaire.settings.open_date' => ['required', 'date'],
            'questionnaire.settings.close_date' => ['required', 'date', 'after:questionnaire.settings.open_date'],
            'questionnaire.settings.deployment_strategy' => ['required', Rule::in(['single', 'per_target'])],

            'questionnaire.questions' => ['required', 'array'],
            'questionnaire.questions.*.id' => ['required', 'integer'],
            'questionnaire.questions.*.order' => ['required', 'integer'],
            'questionnaire.questions.*.required' => ['required', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('questionnaire') && is_string($this->questionnaire)) {
            $questionnaireData = json_decode($this->questionnaire, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($questionnaireData)) {
                $this->merge([
                    'questionnaire' => $questionnaireData
                ]);
            }
        }
    }
}
