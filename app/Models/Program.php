<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Scopes\ProgramAccessScope;

class Program extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'faculty_id',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ProgramAccessScope());
    }
    /**
     * Get the faculty that this program belongs to.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the students enrolled in this program.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the deployed questionnaires for this program.
     */
    public function deployedQuestionnaires(): HasMany
    {
        return $this->hasMany(DeployedQuestionnaire::class);
    }
}