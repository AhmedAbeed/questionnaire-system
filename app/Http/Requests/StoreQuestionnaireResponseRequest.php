<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionnaireResponseRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check(); // Restrict to authenticated users
    }

    public function rules()
    {
        return [
            'responseData' => 'required|json',
            'response' => 'required|array',
            'response.questionnaire_id' => 'required|string',
            'response.start_time' => 'required|string',
            'response.completion_time' => 'required|string',
            'response.time_taken' => 'required|integer',
            'response.responses' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'responseData.required' => 'JSON data is required',
            'responseData.json' => 'The provided data must be a valid JSON string',
            'response.required' => 'The response data could not be processed',
            'response.questionnaire_id.required' => 'Questionnaire ID is required',
            'response.start_time.required' => 'Start time is required',
            'response.completion_time.required' => 'Completion time is required',
            'response.time_taken.required' => 'Time taken is required',
            'response.time_taken.integer' => 'Time taken must be a number',
            'response.responses.required' => 'Responses are required',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('responseData') && is_string($this->responseData)) {
            $responseData = json_decode($this->responseData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($responseData)) {
                $this->merge([
                    'response' => $responseData
                ]);
            }
        }
    }
}