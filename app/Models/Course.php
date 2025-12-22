<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Models\Scopes\CourseAccessScope;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'credit_hours',
        'faculty_id',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::addGlobalScope(new CourseAccessScope());
    }

    /**
     * Get the faculty that offers this course.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the deployed questionnaires for this course.
     */
    public function deployedQuestionnaires()
    {
        return DeployedQuestionnaire::whereHas('targets.semesterCourse', function ($query) {
            $query->where('course_id', $this->id);
        })->get();
    }

    /**
     * Get the semester courses for this course.
     */
    public function semesterCourses(): HasMany
    {
        return $this->hasMany(SemesterCourse::class, 'course_id');
    }
}