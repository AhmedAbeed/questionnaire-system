<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionResponse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'response_id',
        'question_id',
        'text_response',
        'option_id',
        'numeric_value',
    ];

    /**
     * Get the response this question response belongs to.
     */
    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class, 'response_id');
    }

    /**
     * Get the question this response is for.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(DeployedQuestion::class, 'question_id');
    }

    /**
     * Get the option selected in this response.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(DeployedQuestionOption::class, 'option_id');
    }

}