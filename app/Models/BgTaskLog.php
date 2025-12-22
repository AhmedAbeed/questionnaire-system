<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BgTaskLog extends Model
{
    protected $table = 'background_task_logs';
    
    protected $fillable = ['task_id','task_type','type', 'status', 'user_id', 'message', 'data', 'errors', 'file'];

    protected $casts = ['data' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}