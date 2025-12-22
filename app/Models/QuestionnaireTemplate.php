<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionnaireTemplate extends Model
{
    use HasFactory;

    protected $table = 'questionnaire_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'target_type_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'integer',
    ];



    /**
     * Get the questions in this template.
     */
    public function templateQuestions(): HasMany
    {
        return $this->hasMany(TemplateQuestion::class, 'template_id');
    }

    /**
     * Get the deployed questionnaires based on this template.
     */
    public function deployedQuestionnaires(): HasMany
    {
        return $this->hasMany(DeployedQuestionnaire::class, 'template_id');
    }
}