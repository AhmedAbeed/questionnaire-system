<?php

namespace App\Mail;

use App\Models\BgTaskLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class EnrollmentTaskCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public BgTaskLog $taskLog
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubject(),
        );
    }

    public function content(): Content
    {
        $processedData = $this->processTaskData();
        
        return new Content(
            view: 'emails.enrollment-task-completed',
            with: [
                'user' => $this->user,
                'taskData' => $processedData,
            ],
        );
    }

    public function attachments(): array
    {
        if ($this->taskLog->file && Storage::exists($this->taskLog->file)) {
            return [
                Storage::path($this->taskLog->file)
            ];
        }
        
        return [];
    }

    private function processTaskData(): array
    {
        $data = json_decode($this->taskLog->data, true);
        
        return [
            'status' => $this->taskLog->status,
            'task_type' => $this->getTaskType(),
            'start_date' => $this->formatDate($this->taskLog->created_at),
            'end_date' => $this->formatDate($this->taskLog->updated_at),
            'statistics' => [
                'total' => $data['total'] ?? 0,
                'processed' => $data['processed'] ?? 0,
                'successful' => $data['successful'] ?? 0,
                'failed' => $data['failed'] ?? 0,
            ],
            'has_errors' => $this->taskLog->status === 'completed_with_errors' || $this->taskLog->status === 'failed',
            'error_file' => $this->taskLog->file ? Storage::url($this->taskLog->file) : null,
            'error_message' => $data['error'] ?? null,
        ];
    }

    private function getSubject(): string
    {
        return match($this->taskLog->status) {
            'completed' => 'اكتملت مهمة التسجيل بنجاح',
            'completed_with_errors' => 'اكتملت مهمة التسجيل مع وجود أخطاء',
            'failed' => 'فشلت مهمة التسجيل',
            default => 'تحديث حالة مهمة التسجيل'
        };
    }

    private function getTaskType(): string
    {
        return match($this->taskLog->type) {
            'enrollment' => 'تسجيل الطلاب',
            'instructor_enrollment' => 'تسجيل المدرسين',
            default => 'مهمة غير معروفة'
        };
    }

    private function formatDate($date): string
    {
        if (!$date) {
            return 'غير محدد';
        }

        try {
            Carbon::setLocale('ar');
            return Carbon::parse($date)->translatedFormat('d F Y h:i:s A');
        } catch (\Exception $e) {
            return 'غير محدد';
        }
    }
} 