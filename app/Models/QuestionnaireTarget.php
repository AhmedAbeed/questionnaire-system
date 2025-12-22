<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionnaireTarget extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'questionnaire_id',
        'faculty_id',
        'program_id',
        'semester_course_id',
    ];  

    /**
     * Get the questionnaire that owns this target.
     */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(DeployedQuestionnaire::class);
    }

    /**
     * Get the semester course associated with this target.
     */
    public function semesterCourse(): BelongsTo
    {
        return $this->belongsTo(SemesterCourse::class);
    }

    /**
     * Get the faculty associated with this target.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the program associated with this target.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }




    public function getTargetNameAttribute(): ?string
    {
        return match (true) {
            (bool) $this->faculty_id => $this->faculty?->name,
            (bool) $this->program_id => $this->program?->name,
            (bool) $this->semester_course_id => $this->semesterCourse?->course?->name,
            default => null,
        };
    }

    public function getTargetTypeAttribute(): ?string
    {
        return match (true) {
            (bool) $this->faculty_id => 'faculty',
            (bool) $this->program_id => 'program',
            (bool) $this->semester_course_id => 'course',
            default => null,
        };
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::deleted(function ($target) {
            // Check if this was the last target for the questionnaire
            $remainingTargets = static::where('questionnaire_id', $target->questionnaire_id)->count();
            
            if ($remainingTargets === 0) {    
                $target->questionnaire->delete();
            }
        });
    }
}









