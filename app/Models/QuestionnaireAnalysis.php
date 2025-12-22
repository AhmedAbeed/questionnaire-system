<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireAnalysis extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'analysis_data',
        'generated_at',
        'version',
        'status',
        'error_message'
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the questionnaire that owns the analysis.
     */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(DeployedQuestionnaire::class, 'questionnaire_id');
    }

    /**
     * Check if the analysis is still valid (not expired).
     */
    public function isValid(): bool
    {
        // Analysis is valid for 24 hours
        return $this->generated_at->addHours(24)->isFuture();
    }
} 