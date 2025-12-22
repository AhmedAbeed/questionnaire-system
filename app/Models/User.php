<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        'is_active',
        'forced_password_change',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function email(): Attribute
    {
        return new Attribute(
            get: fn ($value) => strtolower($value),
            set: fn ($value) => $value,
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the student record associated with the user.
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Get the external respondent record associated with the user.
     */
    public function externalRespondent(): HasOne
    {
        return $this->hasOne(ExternalRespondent::class, 'user_id');
    }

    /**
     * Get faculty that the user is dean of.
     */
    public function faculty(): HasOne
    {
        return $this->hasOne(Faculty::class, 'dean_user_id');
    }

    /**
     * Get the faculty member record associated with the user.
     */
    public function facultyMember(): HasOne
    {
        return $this->hasOne(FacultyMember::class);
    }

    /**
     * Get the faculty ID for an faculty-dean user who is a dean.
     */
    public function getFacultyIdAttribute(): ?int
    {
        if ($this->isAdmin()) {
            return $this->faculty?->id;
        }
        return null;
    }

    /**
     * Get questionnaire templates created by this user.
     */
    public function createdTemplates(): HasMany
    {
        return $this->hasMany(QuestionnaireTemplate::class, 'created_by');
    }

    /**
     * Get questionnaires created by this user.
     */
    public function createdQuestionnaires(): HasMany
    {
        return $this->hasMany(DeployedQuestionnaire::class, 'creator_id');
    }

    /**
     * Get responses submitted by this user.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'respondent_id');
    }
    
    /**
     * Check if user is a student.
     */
    public function isRespondent(): bool
    {
        return $this->hasRole('respondent');
    }
    
    
    /**
     * Check if user is an faculty-dean.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('faculty-dean');
    }

     /**
     * Check if user is an faculty dean.
     */
    public function isFacultyDean(): bool
    {
        return $this->hasRole('faculty_dean');
    }
    
    /**
     * Check if user is an external respondent.
     */
    public function isExternalRespondent(): bool
    {
        return $this->hasRole('external_respondent');

    }

}
