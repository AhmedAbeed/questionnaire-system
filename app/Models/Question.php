<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'text',
        'description',
        'type_id',
        'category_id',
    ];

   

    /**
     * Get the question type of this question.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(QuestionType::class, 'type_id');
    }

    /**
     * Get the category of this question.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    /**
     * Get the options for this question.
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function hasOptions() {
        // Load the type relationship if not loaded
        if (!$this->relationLoaded('type')) {
            $this->load('type');
        }
        
        return $this->type && $this->type->has_options;
    }

    /**
     * Get the template questions that include this question.
     */
    public function templateQuestions(): HasMany
    {
        return $this->hasMany(TemplateQuestion::class, 'question_id');
    }

    /**
     * Get the deployed questions that include this question.
     */
    public function deployedQuestions(): HasMany
    {
        return $this->hasMany(DeployedQuestion::class, 'question_id');
    }
}