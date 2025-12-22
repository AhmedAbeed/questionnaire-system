<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionnaireTargetType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'scope',
    ];

    /**
     * Get the questionnaire templates with this target type.
     */
    public function templates(): HasMany
    {
        return $this->hasMany(QuestionnaireTemplate::class, 'target_type_id');
    }

    /**
     * Get the deployed questionnaires with this target type.
     */
    public function deployedQuestionnaires(): HasMany
    {
        return $this->hasMany(DeployedQuestionnaire::class, 'target_type_id');
    }
}