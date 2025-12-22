<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lecture extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lecturer_id',
        'semester_id',
        'description',
        'type',
    ];

    /**
     * Relationships
     */
    
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(FacultyMember::class, 'lecturer_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
