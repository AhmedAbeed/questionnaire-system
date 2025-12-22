<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


use Illuminate\Support\Collection;

class QuestionnaireReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Collection $questionnaires
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تذكير بإكمال الاستبيانات المعلقة',
        );
    }

    public function content(): Content
    {
        $processedQuestionnaires = $this->processQuestionnaires();
        return new Content(
            view: 'emails.questionnaire-reminder',

            with: [
                'user' => $this->user,
                'NotAnsweredQuests' => $processedQuestionnaires,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function processQuestionnaires(): array
    {
        return $this->questionnaires->map(function ($questionnaire) {
            return [
                'name' => $questionnaire->name ?? 'استبيان غير محدد',
                'target_type' => $this->getTargetType($questionnaire),
                'target' => $this->getTargetName($questionnaire),
                'deadline_date' => $this->formatDeadlineDate($questionnaire),
                'remaining_time' => $this->calculateRemainingTime($questionnaire),
            ];
        })->toArray();
    }

    private function getTargetType($questionnaire): ?string
    {
        $target = $this->getQuestionnaireTarget($questionnaire);
        return $target?->target_type;
    }

    private function getTargetName($questionnaire): ?string
    {
        $target = $this->getQuestionnaireTarget($questionnaire);
        return $target?->target_name;
    }

    private function getQuestionnaireTarget($questionnaire)
    {
        if ($questionnaire->relationLoaded('targets') && $questionnaire->targets->isNotEmpty()) {
            return $questionnaire->targets->first();
        }

        if (method_exists($questionnaire, 'targets')) {
            try {
                return $questionnaire->targets()->first();
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    private function formatDeadlineDate($questionnaire): string
    {
        if (!$questionnaire->close_date) {
            return 'غير محدد';
        }

        try {
            return Carbon::parse($questionnaire->close_date)->format('Y-m-d');
        } catch (\Exception $e) {
            return 'غير محدد';
        }
    }

    private function calculateRemainingTime($questionnaire): string
    {
        if (!$questionnaire->close_date) {
            return 'غير محدد';
        }

        try {
            Carbon::setLocale('ar');
            return Carbon::parse($questionnaire->close_date)->diffForHumans();
        } catch (\Exception $e) {
            return 'غير محدد';
        }
    }
}