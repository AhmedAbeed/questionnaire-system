<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireReminder extends Model
{
    protected $fillable = [
        'user_id',
        'deployed_questionnaire_id',
        'reminder_count',
        'last_reminder_sent_at',
    ];

    protected $casts = [
        'last_reminder_sent_at' => 'datetime',
    ];

    /**
     * Get the user that received the reminder.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the questionnaire that the reminder was sent for.
     */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(DeployedQuestionnaire::class, 'deployed_questionnaire_id');
    }
} 