<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    private const MAX_TEXT_LENGTH = 255;
    private const VALID_LIKERT_POINTS = [3, 5, 7];
    private const VALID_LIKERT_TYPES = ['custom', 'satisfaction', 'agreement', 'importance'];
    private const MIN_OPTIONS_COUNT = 2;
    private const MIN_LIKERT_OPTIONS_COUNT = 1;

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
        $rules = $this->getBaseRules();

        if ($this->has('questions')) {
            $questionTypes = $this->getQuestionTypes();
            $rules = array_merge($rules, $this->getQuestionTypeSpecificRules($questionTypes));
        }

        return $rules;
    }

    /**
     * Get the base validation rules for questions.
     *
     * @return array<string, string>
     */
    private function getBaseRules(): array
    {
        return [
            'questions' => 'required|array',
            'questions.*.text' => 'required|string|max:' . self::MAX_TEXT_LENGTH . '|unique:questions,text',
            'questions.*.description' => 'nullable|string|max:' . self::MAX_TEXT_LENGTH,
            'questions.*.type_id' => 'required|exists:question_types,id',
            'questions.*.category_id' => 'nullable|exists:question_categories,id',
        ];
    }

    /**
     * Get question types from database with caching.
     *
     * @return array<int, array{id: int, has_options: bool, name: string}>
     */
    private function getQuestionTypes(): array
    {
        return DB::table('question_types')
            ->select(['id', 'has_options', 'name'])
            ->get()
            ->keyBy('id')
            ->toArray();
    }

    /**
     * Get type-specific validation rules for questions.
     *
     * @param array<int, array{id: int, has_options: bool, name: string}> $questionTypes
     * @return array<string, string>
     */
    private function getQuestionTypeSpecificRules(array $questionTypes): array
    {
        $rules = [];

        foreach ($this->input('questions') as $key => $question) {
            if (empty($question['type_id'])) {
                continue;
            }

            $typeId = $question['type_id'];
            $questionType = $questionTypes[$typeId] ?? null;

            if (!$questionType || !$questionType->has_options) {
                continue;
            }

            // Skip validation for Instructor Select questions as options are populated later
            if ($questionType->name === 'Instructor Select') {
                continue;
            }

            if ($questionType->name === 'Likert Scale') {
                $rules = array_merge($rules, $this->getLikertScaleRules($key));
            } else {
                $rules = array_merge($rules, $this->getStandardOptionsRules($key));
            }
        }

        return $rules;
    }

    /**
     * Get validation rules for Likert scale questions.
     *
     * @param int $key
     * @return array<string, string>
     */
    private function getLikertScaleRules(int $key): array
    {
        return [
            "questions.$key.likert_points" => 'required|integer|in:' . implode(',', self::VALID_LIKERT_POINTS),
            "questions.$key.likert_type" => 'required|string|in:' . implode(',', self::VALID_LIKERT_TYPES),
            "questions.$key.options" => 'required|array|min:' . self::MIN_LIKERT_OPTIONS_COUNT,
            "questions.$key.options.*" => 'required|string|max:' . self::MAX_TEXT_LENGTH,
            "questions.$key.values" => 'required|array|min:' . self::MIN_LIKERT_OPTIONS_COUNT,
            "questions.$key.values.*" => 'required|integer|min:1',
            "questions.$key.orders" => 'required|array|min:' . self::MIN_LIKERT_OPTIONS_COUNT,
            "questions.$key.orders.*" => 'required|integer|min:1',
        ];
    }

    /**
     * Get validation rules for standard options questions.
     *
     * @param int $key
     * @return array<string, string>
     */
    private function getStandardOptionsRules(int $key): array
    {
        return [
            "questions.$key.options" => 'required|array|min:' . self::MIN_OPTIONS_COUNT,
            "questions.$key.options.*" => 'required|string|max:' . self::MAX_TEXT_LENGTH,
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];
        
        // Base messages
        $baseMessages = [
            'questions.required' => __('The questions field is required.'),
            'questions.array' => __('The questions must be an array.'),
        ];
        
        // Get the questions array from the request
        $questions = $this->input('questions', []);
        
        // Generate dynamic messages for each question
        foreach ($questions as $index => $question) {
            $questionNumber = $index + 1;
            
            $messages["questions.{$index}.text.required"] = __("Question #:number: The question text is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.text.string"] = __("Question #:number: The question text must be a string.", ['number' => $questionNumber]);
            $messages["questions.{$index}.text.max"] = __("Question #:number: The question text cannot exceed :max characters.", ['number' => $questionNumber, 'max' => self::MAX_TEXT_LENGTH]);
            $messages["questions.{$index}.text.unique"] = __("Question #:number: This question text already exists.", ['number' => $questionNumber]);
            
            $messages["questions.{$index}.description.string"] = __("Question #:number: The description must be a string.", ['number' => $questionNumber]);
            $messages["questions.{$index}.description.max"] = __("Question #:number: The description cannot exceed :max characters.", ['number' => $questionNumber, 'max' => self::MAX_TEXT_LENGTH]);
            
            $messages["questions.{$index}.type_id.required"] = __("Question #:number: The question type is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.type_id.exists"] = __("Question #:number: The selected question type is invalid.", ['number' => $questionNumber]);
            
            $messages["questions.{$index}.category_id.exists"] = __("Question #:number: The selected question category is invalid.", ['number' => $questionNumber]);
            
            // Likert Scale specific messages
            $messages["questions.{$index}.likert_points.required"] = __("Question #:number: The number of Likert points is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.likert_points.integer"] = __("Question #:number: The Likert points must be a number.", ['number' => $questionNumber]);
            $messages["questions.{$index}.likert_points.in"] = __("Question #:number: The Likert points must be one of: :points", ['number' => $questionNumber, 'points' => implode(', ', self::VALID_LIKERT_POINTS)]);
            
            $messages["questions.{$index}.likert_type.required"] = __("Question #:number: The Likert type is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.likert_type.string"] = __("Question #:number: The Likert type must be a string.", ['number' => $questionNumber]);
            $messages["questions.{$index}.likert_type.in"] = __("Question #:number: The Likert type must be one of: :types", ['number' => $questionNumber, 'types' => implode(', ', self::VALID_LIKERT_TYPES)]);
            
            $messages["questions.{$index}.options.required"] = __("Question #:number: The options field is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.options.array"] = __("Question #:number: The options must be an array.", ['number' => $questionNumber]);
            $messages["questions.{$index}.options.min"] = __("Question #:number: The options must have at least :min items.", ['number' => $questionNumber]);
            $messages["questions.{$index}.options.*.required"] = __("Question #:number: The option text is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.options.*.string"] = __("Question #:number: The option text must be a string.", ['number' => $questionNumber]);
            $messages["questions.{$index}.options.*.max"] = __("Question #:number: The option text cannot exceed :max characters.", ['number' => $questionNumber, 'max' => self::MAX_TEXT_LENGTH]);
            
            $messages["questions.{$index}.values.required"] = __("Question #:number: The values field is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.values.array"] = __("Question #:number: The values must be an array.", ['number' => $questionNumber]);
            $messages["questions.{$index}.values.min"] = __("Question #:number: The values must have at least :min items.", ['number' => $questionNumber]);
            $messages["questions.{$index}.values.*.required"] = __("Question #:number: The value is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.values.*.integer"] = __("Question #:number: The value must be a number.", ['number' => $questionNumber]);
            $messages["questions.{$index}.values.*.min"] = __("Question #:number: The value must be at least 1.", ['number' => $questionNumber]);
            
            $messages["questions.{$index}.orders.required"] = __("Question #:number: The orders field is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.orders.array"] = __("Question #:number: The orders must be an array.", ['number' => $questionNumber]);
            $messages["questions.{$index}.orders.min"] = __("Question #:number: The orders must have at least :min items.", ['number' => $questionNumber]);
            $messages["questions.{$index}.orders.*.required"] = __("Question #:number: The order is required.", ['number' => $questionNumber]);
            $messages["questions.{$index}.orders.*.integer"] = __("Question #:number: The order must be a number.", ['number' => $questionNumber]);
            $messages["questions.{$index}.orders.*.min"] = __("Question #:number: The order must be at least 1.", ['number' => $questionNumber]);
        }
        
        return array_merge($baseMessages, $messages);
    }
}