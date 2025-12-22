<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;



class Faculty extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'faculties';

    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'dean_user_id',
    ];
   

    /**
     * Get the dean of this faculty.
     */
    public function dean(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_user_id');
    }

    /**
     * Get the programs that belong to this faculty.
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * Get the courses that belong to this faculty.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function facultyMembers():HasMany
    {
        return $this->hasMany(facultyMember::class);
    }
}