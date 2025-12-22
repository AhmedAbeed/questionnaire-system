<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\DeployedQuestionnaire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use App\Mail\QuestionnaireSubmittedMail;
use Illuminate\Notifications\Notification;
use App\Models\Response;

class QuestionnaireSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public DeployedQuestionnaire $questionnaire, public Response $response)
    {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): Mailable
    {
        return (new QuestionnaireSubmittedMail($this->questionnaire, $this->response))
            ->to($notifiable->email);
    }
}
