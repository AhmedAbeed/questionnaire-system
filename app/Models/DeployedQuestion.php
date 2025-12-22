<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeployedQuestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'questionnaire_id',
        'question_id',
        'is_required',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Get the questionnaire this question belongs to.
     */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(DeployedQuestionnaire::class, 'questionnaire_id');
    }

    /**
     * Get the question this deployed question is based on.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    /**
     * Get the options for this deployed question.
     */
    public function options(): HasMany
    {
        return $this->hasMany(DeployedQuestionOption::class, 'deployed_question_id');
    }

    public function hasOptions() {
        return $this->options()->count() > 0; 
    }

    /**
     * Get the responses to this question.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class, 'question_id');
    }

    /**
     * Get the type of this question via the base question.
     */
    public function type()
    {
        return $this->question->type();
    }

    /**
     * Get the text of this question via the base question.
     */
    public function getText()
    {
        return $this->question->text;
    }

}