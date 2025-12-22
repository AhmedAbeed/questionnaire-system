<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Mail\QuestionnaireReminderMail;
use App\Models\DeployedQuestionnaire;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class QuestionnaireReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user, public $questionnaires)
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): QuestionnaireReminderMail
    {
        return (new QuestionnaireReminderMail($this->user, $this->questionnaires))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'questionnaires' => $this->questionnaires->map(fn($q) => [
                'id' => $q->id,
                'name' => $q->name
            ])->toArray(),
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'reminder_type' => 'questionnaire',
            'message' => 'تذكير بإكمال الاستبيانات المعلقة'
        ];
    }
}
