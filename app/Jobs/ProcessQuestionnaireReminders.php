<?php

namespace App\Jobs;

use App\Models\DeployedQuestionnaire;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Models\QuestionnaireReminder;
use Illuminate\Support\Collection;


class ProcessQuestionnaireReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const BATCH_SIZE = 500;
    
    /** @var int Cache time-to-live in seconds */
    const CACHE_TTL = 300;
    
    /** @var int Delay between retry attempts in seconds */
    const BACKOFF_DELAY = 180;

    /** @var int Maximum execution time in seconds */
    public $timeout = 10800;
    
    /** @var int Time to wait before retrying failed jobs */
    public $backoff = self::BACKOFF_DELAY;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('reminders');
    }

    /**
     * Execute the job.
     *
     * @throws \Exception When processing fails
     */
    public function handle(): void
    {
        try {
            $stats = $this->processOptimizedReminders();
        } catch (\Exception $e) {
            Log::error('Optimized questionnaire reminder job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Process reminders using optimized database queries.
     *
     * @return array Statistics about the processing run
     */
    private function processOptimizedReminders(): array
    {
        $stats = [
            'total_questionnaires' => 0,
            'total_reminders_sent' => 0,
            'users_notified' => 0
        ];

        $questionnaires = $this->getActiveQuestionnairesOptimized();
        $stats['total_questionnaires'] = $questionnaires->count();
        
        if (!$questionnaires->isEmpty()) {
            $userQuestionnaireMappings = $this->buildOptimizedUserMappings($questionnaires);
            foreach ($userQuestionnaireMappings as $userMapping) {
                $this->sendUserQuestionnaireReminder($userMapping);
                $stats['total_reminders_sent'] += count($userMapping['questionnaire_ids']);
                $stats['users_notified']++;
            }
        }

        return $stats;
    }

    /**
     * Retrieve active questionnaires with their targets.
     *
     * @return \Illuminate\Support\Collection Collection of active questionnaires
     */
    private function getActiveQuestionnairesOptimized(): Collection
    {
        return DeployedQuestionnaire::select([
            'id', 'name', 'close_date', 'created_at'
        ])
        ->where('status', 'active')
        ->where('close_date', '>=', now())
        ->with(['targets:id,questionnaire_id,faculty_id,program_id,semester_course_id'])
        ->get();
    }

    /**
     * Build mappings between users and their unanswered questionnaires.
     *
     * @param \Illuminate\Support\Collection $questionnaires Collection of active questionnaires
     * @return array Array of user mappings with their questionnaire IDs
     */
    private function buildOptimizedUserMappings(Collection $questionnaires): array
    {
        $rawSql = "
            WITH questionnaire_targets AS (
                SELECT 
                    qt.questionnaire_id,
                    qt.faculty_id,
                    qt.program_id,
                    qt.semester_course_id
                FROM questionnaire_targets qt
                WHERE qt.questionnaire_id IN (" . $questionnaires->pluck('id')->implode(',') . ")
            ),
            eligible_users AS (
                SELECT DISTINCT 
                    u.id AS user_id,
                    u.full_name,
                    u.email,
                    qt.questionnaire_id
                FROM users u
                INNER JOIN students s ON s.user_id = u.id
                INNER JOIN programs p ON p.id = s.program_id
                INNER JOIN questionnaire_targets qt ON (
                    qt.faculty_id = p.faculty_id OR
                    qt.program_id = p.id OR
                    qt.semester_course_id IN (
                        SELECT sc.id 
                        FROM enrollments se 
                        INNER JOIN semester_courses sc ON sc.id = se.semester_course_id 
                        WHERE se.student_id = s.id
                    )
                )
            ),
            user_responses AS (
                SELECT DISTINCT
                    qr.user_id,
                    qr.questionnaire_id
                FROM responses qr
                WHERE qr.questionnaire_id IN (" . $questionnaires->pluck('id')->implode(',') . ")
            ),
            unanswered AS (
                SELECT 
                    eu.user_id,
                    eu.full_name,
                    eu.email,
                    eu.questionnaire_id
                FROM eligible_users eu
                LEFT JOIN user_responses ur 
                    ON eu.user_id = ur.user_id AND eu.questionnaire_id = ur.questionnaire_id
                WHERE ur.user_id IS NULL
            )
            SELECT 
                user_id,
                questionnaire_id
            FROM unanswered
            ORDER BY user_id, questionnaire_id;
        ";

        $results = DB::select($rawSql);
        
        $userMappings = [];
        $questionnaireData = $questionnaires->keyBy('id');
        
        foreach ($results as $row) {            
            if (!isset($userMappings[$row->user_id])) {
                $userMappings[$row->user_id] = [
                    'user_id' => $row->user_id,
                    'questionnaire_ids' => [],
                ];
            }
            
            $userMappings[$row->user_id]['questionnaire_ids'][] = $row->questionnaire_id;
        }

        return $userMappings;
    }

    /**
     * Dispatch a job to send reminders for a user's questionnaires.
     *
     * @param array $userMapping Array containing user_id and questionnaire_ids
     */
    private function sendUserQuestionnaireReminder(array $userMapping): void
    {
        SendUserQuestionnaireReminder::dispatch($userMapping);
    }


}