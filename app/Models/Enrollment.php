<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Scopes\EnrollmentAccessScope;


class Enrollment extends Model
{
    protected $fillable = ['student_id', 'semester_course_id'];

    protected static function booted()
    {
        static::addGlobalScope(new EnrollmentAccessScope());
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semesterCourse(): BelongsTo
    {
        return $this->belongsTo(SemesterCourse::class);
    }
}
