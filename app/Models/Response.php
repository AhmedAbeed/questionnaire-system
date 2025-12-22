<?php

namespace App\Models;

use App\Models\Scopes\ResponseAccessScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Response extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'questionnaire_id',
        'user_id',
        'anonymous_token',
        'time_taken',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ResponseAccessScope());
    }

    /**
     * Get the questionnaire this response belongs to.
     */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(DeployedQuestionnaire::class, 'questionnaire_id');
    }

    /**
     * Get the user who submitted this response.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the individual question responses for this response.
     */
    public function questionResponses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class, 'response_id');
    }

    /**
     * Check if this response is anonymous.
     */
    public function isAnonymous(): bool
    {
        return $this->user_id === null && $this->anonymous_token !== null;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($response) {
            // Delete all associated question responses
            $response->questionResponses()->delete();
        });
    }
}