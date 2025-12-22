<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_task_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task_id')->index();
            $table->string('task_type');
            $table->string('type')->index();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('message')->nullable();
            $table->json('data')->nullable(); 
            $table->text('errors')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_task_logs');
    }
};