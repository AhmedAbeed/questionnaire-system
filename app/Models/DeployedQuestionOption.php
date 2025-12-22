<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeployedQuestionOption extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'deployed_question_id',
        'option_text',
        'value',
        'order',
    ];

    /**
     * Get the question this option belongs to.
     */
    public function deployedQuestion(): BelongsTo
    {
        return $this->belongsTo(DeployedQuestion::class, 'deployed_question_id');
    }
}