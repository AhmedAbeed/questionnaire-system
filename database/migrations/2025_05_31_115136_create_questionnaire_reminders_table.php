<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questionnaire_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('deployed_questionnaire_id')->constrained()->onDelete('cascade');
            $table->integer('reminder_count')->default(1);
            $table->timestamp('last_reminder_sent_at');
            $table->timestamps();

            $table->unique(['user_id', 'deployed_questionnaire_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaire_reminders');
    }
}; 