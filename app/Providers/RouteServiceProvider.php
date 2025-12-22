<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot()
    {
        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Custom route groups
            Route::middleware('web')->group(base_path('routes/auth.php'));
            Route::middleware('web')->group(base_path('routes/session.php'));
            Route::middleware('web')->group(base_path('routes/home.php'));
            Route::middleware('web')->group(base_path('routes/admin.php'));
            Route::middleware('web')->group(base_path('routes/faculty-dean.php'));
            Route::middleware('web')->group(base_path('routes/respondent.php'));
            Route::middleware('web')->group(base_path('routes/response.php'));
            Route::middleware('web')->group(base_path('routes/admin-dashboard.php'));
            Route::middleware('web')->group(base_path('routes/deployed-questionnaire.php'));
            Route::middleware('web')->group(base_path('routes/enrollment.php'));
            Route::middleware('web')->group(base_path('routes/program.php'));
            Route::middleware('web')->group(base_path('routes/course.php'));
            Route::middleware('web')->group(base_path('routes/faculty.php'));
            Route::middleware('web')->group(base_path('routes/faculty-member.php'));
            Route::middleware('web')->group(base_path('routes/quality-manager.php'));
            Route::middleware('web')->group(base_path('routes/student.php'));
            Route::middleware('web')->group(base_path('routes/question.php'));
            Route::middleware('web')->group(base_path('routes/questionniare-template.php'));

        });
    }
}
