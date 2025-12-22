<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionnaireTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('questionnaire_templates')->where(function ($query) {
                    return $query->where('is_active', true);
                })
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['required', 'exists:questions,id'],
            'questions.*.order' => ['required', 'integer', 'min:0'],
            'questions.*.is_required' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('The name field is required.'),
            'name.string' => __('The name must be a string.'),
            'name.max' => __('The name cannot exceed 100 characters.'),
            'name.unique' => __('This name is already taken for an active template.'),
            
            'description.string' => __('The description must be a string.'),
            
            'is_active.required' => __('The is active field is required.'),
            'is_active.boolean' => __('The is active field must be true or false.'),
            
            'questions.required' => __('The questions field is required.'),
            'questions.array' => __('The questions must be an array.'),
            'questions.min' => __('At least one question is required.'),
            
            'questions.*.id.required' => __('The question ID is required.'),
            'questions.*.id.exists' => __('The selected question does not exist.'),
            
            'questions.*.order.required' => __('The question order is required.'),
            'questions.*.order.integer' => __('The question order must be a number.'),
            'questions.*.order.min' => __('The question order must be at least 0.'),
            
            'questions.*.is_required.required' => __('The question required status is required.'),
            'questions.*.is_required.boolean' => __('The question required status must be true or false.'),
        ];
    }
} 