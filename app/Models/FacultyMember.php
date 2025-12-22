<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacultyMember extends Model
{
    use HasFactory;

    protected $table = 'faculty_members';

    protected $fillable = [
        'user_id',
        'national_id',
        'academic_email',
        'personal_email',
        'phone_number',
        'faculty_id',
        'position',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function semesterCourses()
    {
        return $this->hasMany(SemesterCourseInstructor::class);
    }
}
