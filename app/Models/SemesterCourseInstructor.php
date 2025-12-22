<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SemesterCourseInstructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_course_id',
        'faculty_member_id',
        'is_primary',
        'status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function semesterCourse()
    {
        return $this->belongsTo(SemesterCourse::class);
    }

    public function facultyMember()
    {
        return $this->belongsTo(FacultyMember::class);
    }
}
