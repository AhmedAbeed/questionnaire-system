<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Mail\EnrollmentTaskCompletedMail;
use App\Models\BgTaskLog;
use App\Models\User;

class EnrollmentTaskCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BgTaskLog $taskLog)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): EnrollmentTaskCompletedMail
    {
        return (new EnrollmentTaskCompletedMail($notifiable, $this->taskLog))
            ->to($notifiable->email);
    }

    public function toArray(object $notifiable): array
    {
        $data = json_decode($this->taskLog->data, true);
        
        return [
            'task_id' => $this->taskLog->task_id,
            'type' => $this->taskLog->type,
            'status' => $this->taskLog->status,
            'statistics' => [
                'total' => $data['total'] ?? 0,
                'processed' => $data['processed'] ?? 0,
                'successful' => $data['successful'] ?? 0,
                'failed' => $data['failed'] ?? 0,
            ],
            'message' => $this->taskLog->message,
            'file' => $this->taskLog->file,
            'created_at' => $this->taskLog->created_at,
            'completed_at' => $this->taskLog->completed_at,
        ];
    }
} 