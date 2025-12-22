<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Base Repository
use App\Contracts\RepositoryInterface;
use App\Repositories\BaseRepository;
use App\Contracts\UnitOfWorkInterface;
use App\Repositories\UnitOfWork;

// User Management
use App\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Contracts\FacultyRepositoryInterface;
use App\Repositories\FacultyRepository;
use App\Contracts\StudentRepositoryInterface;
use App\Repositories\StudentRepository;
use App\Contracts\FacultyMemberRepositoryInterface;
use App\Repositories\FacultyMemberRepository;

// Academic Structure
use App\Contracts\OrganizationalUnitRepositoryInterface;
use App\Repositories\OrganizationalUnitRepository;
use App\Contracts\CourseRepositoryInterface;
use App\Repositories\CourseRepository;
use App\Contracts\ProgramRepositoryInterface;
use App\Repositories\ProgramRepository;
use App\Contracts\SemesterRepositoryInterface;
use App\Repositories\SemesterRepository;
use App\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\EnrollmentRepository;

// Questionnaire Related
use App\Contracts\QuestionnaireTemplateRepositoryInterface;
use App\Repositories\QuestionnaireTemplateRepository;
use App\Contracts\DeployedQuestionnaireRepositoryInterface;
use App\Repositories\DeployedQuestionnaireRepository;
use App\Contracts\QuestionRepositoryInterface;
use App\Repositories\QuestionRepository;
use App\Contracts\QuestionOptionsRepositoryInterface;
use App\Repositories\QuestionOptionsRepository;
use App\Contracts\QuestionnaireTargetTypesInterface;
use App\Repositories\QuestionnaireTargetTypesRepository;
use App\Contracts\ResponseRepositoryInterface;
use App\Repositories\ResponseRepository;
use App\Contracts\DeployedQuestionRepositoryInterface;
use App\Repositories\DeployedQuestionRepository;
use App\Contracts\DeployedQuestionOptionRepositoryInterface;
use App\Repositories\DeployedQuestionOptionRepository;
use App\Contracts\DeployedQuestionnaireTargetRepositoryInterface;
use App\Repositories\DeployedQuestionnaireTargetRepository;
use App\Contracts\BgTaskLogRepositoryInterface;
use App\Repositories\BgTaskLogRepository;
use App\Contracts\SemesterCourseRepositoryInterface;
use App\Repositories\SemesterCourseRepository;
use App\Contracts\QuestionResponseRepositoryInterface;
use App\Repositories\QuestionResponseRepository;
use App\Contracts\QuestionTypeRepositoryInterface;
use App\Repositories\QuestionTypeRepository;
use App\Contracts\QuestionCategoryRepositoryInterface;
use App\Repositories\QuestionCategoryRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings in the container.
     *
     * @return void
     */
    public function register(): void
    {
        // Base repositories
        $this->app->bind(UnitOfWorkInterface::class, UnitOfWork::class);

        // User Management repositories
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(FacultyRepositoryInterface::class, FacultyRepository::class);
        $this->app->bind(StudentRepositoryInterface::class, StudentRepository::class);
        $this->app->bind(FacultyMemberRepositoryInterface::class, FacultyMemberRepository::class);

        // Academic Structure repositories
        $this->app->bind(CourseRepositoryInterface::class, CourseRepository::class);
        $this->app->bind(ProgramRepositoryInterface::class, ProgramRepository::class);
        $this->app->bind(SemesterRepositoryInterface::class, SemesterRepository::class);
        $this->app->bind(EnrollmentRepositoryInterface::class, EnrollmentRepository::class);

        // Questionnaire related repositories
        $this->app->bind(QuestionnaireTemplateRepositoryInterface::class, QuestionnaireTemplateRepository::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(QuestionOptionsRepositoryInterface::class, QuestionOptionsRepository::class);
        $this->app->bind(QuestionnaireTargetTypesInterface::class, QuestionnaireTargetTypesRepository::class);
        $this->app->bind(ResponseRepositoryInterface::class, ResponseRepository::class);
        $this->app->bind(DeployedQuestionnaireRepositoryInterface::class, DeployedQuestionnaireRepository::class);
        $this->app->bind(DeployedQuestionRepositoryInterface::class, DeployedQuestionRepository::class);
        $this->app->bind(DeployedQuestionOptionRepositoryInterface::class, DeployedQuestionOptionRepository::class);
        $this->app->bind(DeployedQuestionnaireTargetRepositoryInterface::class, DeployedQuestionnaireTargetRepository::class);
        $this->app->bind(QuestionResponseRepositoryInterface::class, QuestionResponseRepository::class);
        $this->app->bind(QuestionTypeRepositoryInterface::class, QuestionTypeRepository::class);
        $this->app->bind(QuestionCategoryRepositoryInterface::class, QuestionCategoryRepository::class);

        // Additional repositories
        $this->app->bind(SemesterCourseRepositoryInterface::class, SemesterCourseRepository::class);
        $this->app->bind(BgTaskLogRepositoryInterface::class, BgTaskLogRepository::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}