<?php

namespace App\Listeners;

use App\Events\QuestionnaireSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\QuestionnaireSubmittedNotification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

class SendQuestionnaireSubmissionNotification implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'notifications';

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 30;

    /**
     * The number of times to attempt the job.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     */
    public function handle(QuestionnaireSubmitted $event): void
    {
        try {
            $event->user->notify(new QuestionnaireSubmittedNotification($event->deployedQuestionnaire, $event->response));
        } catch (Exception $e) {
           LogError('Failed to send questionnaire submission notification', 'SendQuestionnaireSubmissionNotification', $e);
        }
    }
}
