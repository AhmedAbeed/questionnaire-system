<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Scopes\DeployedQuestionnaireAccessScope;

class DeployedQuestionnaire extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'template_id',
        'name',
        'target_type_id',
        'open_date',
        'close_date',
        'status',
        'creator_id',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::addGlobalScope(new DeployedQuestionnaireAccessScope());
        static::deleting(function ($deployedQs) {
            foreach ($deployedQs->responses as $response) {
                $response->delete();
            }
        });
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'open_date' => 'datetime',
        'close_date' => 'datetime',
    ];

    /**
     * Get the template this questionnaire is based on.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class, 'template_id');
    }

    /**
     * Get the target type of this questionnaire.
     */
    public function targetType(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTargetType::class, 'target_type_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(QuestionnaireTarget::class, 'questionnaire_id');
    }


    /**
     * Get the user who created this questionnaire.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the questions in this questionnaire.
     */
    public function deployedQuestions(): HasMany
    {
        return $this->hasMany(DeployedQuestion::class, 'questionnaire_id');
    }

    /**
     * Get the responses to this questionnaire.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'questionnaire_id');
    }

    /**
     * Check if the questionnaire is open for responses.
     */
    public function isOpen(): bool
    {
        $now = now();
        return $this->status === 'active' && $now->between($this->open_date, $this->close_date);
    }

    /**
     * Get the count of eligible respondents.
     *
     * @return int
     */
    public function getEligibleRespondentsCount(): int
    {
        try {
            $targetType = $this->targetType?->scope;
            $targetRole = $this->targetType?->name;

            // Initialize count
            $count = 0;

            // Get targets with relationships without global scope
            $targets = $this->targets()->withoutGlobalScopes()->with(['faculty', 'program', 'semesterCourse'])->get();

            // Find specific target
            $faculty = $targets->firstWhere('faculty_id', '!=', null)?->faculty;
            $program = $targets->firstWhere('program_id', '!=', null)?->program;
            $semesterCourse = $targets->firstWhere('semester_course_id', '!=', null)?->semesterCourse;

            if ($targetType === 'academic' && $targetRole === 'student') {
                if ($program) {
                    $count = Student::withoutGlobalScopes()->where('program_id', $program->id)->count();
                } elseif ($semesterCourse) {
                    $count = Student::withoutGlobalScopes()->whereHas('enrollments', function ($query) use ($semesterCourse) {
                        $query->where('semester_course_id', $semesterCourse->id);
                    })->count();
                } elseif ($faculty) {
                    $count = Student::withoutGlobalScopes()->whereHas('program', function ($query) use ($faculty) {
                        $query->where('faculty_id', $faculty->id);
                    })->count();
                } else {
                    \Log::warning('No specific target found for academic student questionnaire', [
                        'questionnaire_id' => $this->id,
                        'faculty_id' => $faculty?->id,
                        'program_id' => $program?->id,
                        'semester_course_id' => $semesterCourse?->id,
                    ]);
                    $count = 0; // Avoid counting all students
                }
            } else {
                $count = 0;
            }

            return $count;
        } catch (\Exception $e) {
            \Log::error('Failed to calculate eligible respondents count', [
                'questionnaire_id' => $this->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }

    public function isRespondentEligible()
    {
        // Get current authenticated user with necessary relationships
        $respondent = auth()->user()->load('student.program.faculty', 'student.enrollments.semesterCourse');
        
        // Extract relevant IDs
        $facultyId = $respondent->student?->program?->faculty?->id;
        $programId = $respondent->student?->program?->id;
        $semesterCourseIds = $respondent->student?->enrollments
            ->pluck('semesterCourse.id')
            ->filter()
            ->all();

        // Check if the questionnaire is active, not expired, targets the respondent,
        // and hasn't been answered by them yet
        return self::query()
            ->where('id', $this->id)
            ->where('status', 'active')
            ->where('close_date', '>=', now())
            ->whereHas('targets', function ($query) use ($facultyId, $programId, $semesterCourseIds) {
                $query->where(function ($q) use ($facultyId, $programId, $semesterCourseIds) {
                    $q->where('faculty_id', $facultyId)
                    ->orWhere('program_id', $programId)
                    ->orWhereIn('semester_course_id', $semesterCourseIds);
                });
            })
            ->whereDoesntHave('responses', function ($query) use ($respondent) {
                $query->where('user_id', $respondent->id);
            })
            ->exists();
    }

    /**
     * Get the completion rate for the questionnaire.
     *
     * @return string
     */
    public function getCompletionRateAttribute(): string
    {
        $responseCount = $this->questionnaireResponseCount();
        $eligibleRespondentsCount = $this->getEligibleRespondentsCount();

        $rate = $eligibleRespondentsCount > 0
            ? round(($responseCount / $eligibleRespondentsCount) * 100, 2)
            : 0;

        return $rate . '%';
    }

    public function questionnaireResponseCount()
    {
        return $this->responses()->withoutGlobalScopes()->count();
    }

}