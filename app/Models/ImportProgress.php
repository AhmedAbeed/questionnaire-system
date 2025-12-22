<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'type',
        'total_rows',
        'processed_rows',
        'status',
        'error_message',
        'errors',
        'user_id',
        'completed_at'
    ];

    protected $casts = [
        'errors' => 'json',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that initiated the import
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate the import progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->total_rows == 0) {
            return 0;
        }

        return min(100, round(($this->processed_rows / $this->total_rows) * 100));
    }

    /**
     * Check if there are any errors
     */
    public function getHasErrorsAttribute()
    {
        return !empty($this->errors) || !empty($this->error_message);
    }

    /**
     * Get all errors, both row-specific and general
     */
    public function getAllErrorsAttribute()
    {
        $allErrors = [];
        
        // Add the general error message if exists
        if (!empty($this->error_message)) {
            $allErrors[] = [
                'type' => 'general',
                'message' => $this->error_message,
                'time' => $this->updated_at->toDateTimeString()
            ];
        }
        
        // Add row-specific errors
        if (!empty($this->errors)) {
            foreach ($this->errors as $error) {
                $allErrors[] = array_merge(['type' => 'row'], $error);
            }
        }
        
        return $allErrors;
    }
}