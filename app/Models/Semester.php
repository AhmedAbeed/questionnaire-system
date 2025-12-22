<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'start_date',
        'end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the deployed questionnaires for this semester.
     */
    public function deployedQuestionnaires(): HasMany
    {
        return $this->hasMany(DeployedQuestionnaire::class, 'semester_id');
    }

    /**
     * Check if the semester is current.
     */
    public function isCurrent(): bool
    {
        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }
}