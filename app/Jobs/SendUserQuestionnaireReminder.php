<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\DeployedQuestionnaire;
use App\Notifications\QuestionnaireReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendUserQuestionnaireReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userMapping;

    /**
     * Create a new job instance.
     */
    public function __construct(array $userMapping)
    {
        $this->userMapping = $userMapping;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userMapping['user_id']);
        $questionnaires = DeployedQuestionnaire::whereIn('id', $this->userMapping['questionnaire_ids'])
        ->select('id', 'name', 'open_date', 'close_date')
        ->with('targets')
        ->get();

        try {
        $user->notify(new QuestionnaireReminderNotification($user, $questionnaires));
        } catch (\Exception $e) {
            Log::error('Failed to send questionnaire reminder: ' . $e->getMessage());
        }
        
    }
}