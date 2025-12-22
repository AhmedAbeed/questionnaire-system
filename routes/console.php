<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ProcessQuestionnaireReminders;
use App\Jobs\AnalyzeQuestionnaire;


// Daily database backup at 2 AM
// Schedule::command('backup:run --only-db')
// ->daily()
// ->at('02:00')
// ->emailOutputOnFailure(env('BACKUP_MAIL_TO'));

// Schedule::job(new ProcessQuestionnaireReminders)->everyMinute();

// Run AI analysis for ended questionnaires hourly
Schedule::job(new AnalyzeQuestionnaire)->everyMinute();


