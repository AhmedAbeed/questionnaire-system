<?php

namespace App\Repositories;

use App\Contracts\DeployedQuestionnaireRepositoryInterface;
use App\Contracts\DeployedQuestionRepositoryInterface;
use App\Contracts\StudentRepositoryInterface;
use App\Contracts\QuestionRepositoryInterface;
use App\Contracts\QuestionOptionsRepositoryInterface;
use App\Contracts\UnitOfWorkInterface;
use App\Contracts\QuestionnaireTemplateRepositoryInterface;
use App\Contracts\FacultyRepositoryInterface;
use App\Contracts\ProgramRepositoryInterface;
use App\Contracts\CourseRepositoryInterface;
use App\Contracts\SemesterRepositoryInterface;
use App\Contracts\QuestionnaireTargetTypesInterface;
use App\Contracts\ResponseRepositoryInterface;
use App\Contracts\EnrollmentRepositoryInterface;
use App\Contracts\DeployedQuestionOptionRepositoryInterface;
use App\Contracts\DeployedQuestionnaireTargetRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\FacultyMemberRepositoryInterface;
use App\Contracts\BgTaskLogRepositoryInterface;
use App\Contracts\SemesterCourseRepositoryInterface;
use App\Contracts\QuestionResponseRepositoryInterface;
use App\Contracts\QuestionTypeRepositoryInterface;
use App\Contracts\QuestionCategoryRepositoryInterface;

use Illuminate\Support\Facades\DB;

class UnitOfWork implements UnitOfWorkInterface
{
    // Questionnaire related repositories
    protected $questionnaireTemplateRepository;
    protected $deployedQuestionnaireRepository;
    protected $deployedQuestionnaireTargetRepository;
    protected $questionnaireTargetTypesRepository;
    
    // Question related repositories
    protected $questionRepository;
    protected $deployedQuestionRepository;
    protected $deployedQuestionOptionRepository;
    protected $questionOptionsRepository;
    
    // Academic structure repositories
    protected $facultyRepository;
    protected $programRepository;
    protected $courseRepository;
    protected $semesterRepository;
    
    // User related repositories
    protected $studentRepository;
    protected $facultyMemberRepository;

    protected $enrollmentRepository;
    protected $responseRepository;
    protected $userRepository;

    protected $inTransaction = false;

    protected $semesterCourseRepository;
    protected $bgTaskLogRepository;

    protected $questionResponseRepository;

    protected $questionTypeRepository;
    protected $questionCategoryRepository;

    public function __construct(
        StudentRepositoryInterface $studentRepository,
        QuestionRepositoryInterface $questionRepository,
        QuestionOptionsRepositoryInterface $questionOptionsRepository,
        QuestionnaireTemplateRepositoryInterface $questionnaireTemplateRepository,
        DeployedQuestionnaireRepositoryInterface $deployedQuestionnaireRepository,
        FacultyRepositoryInterface $facultyRepository,
        ProgramRepositoryInterface $programRepository,
        CourseRepositoryInterface $courseRepository,
        SemesterRepositoryInterface $semesterRepository,
        QuestionnaireTargetTypesInterface $questionnaireTargetTypesRepository,
        ResponseRepositoryInterface $responseRepository,
        EnrollmentRepositoryInterface $enrollmentRepository,
        DeployedQuestionRepositoryInterface $deployedQuestionRepository,
        DeployedQuestionOptionRepositoryInterface $deployedQuestionOptionRepository,
        DeployedQuestionnaireTargetRepositoryInterface $deployedQuestionnaireTargetRepository,
        UserRepositoryInterface $userRepository,
        FacultyMemberRepositoryInterface $facultyMemberRepository,
        SemesterCourseRepositoryInterface $semesterCourseRepository,
        BgTaskLogRepositoryInterface $bgTaskLogRepository,
        QuestionResponseRepositoryInterface $questionResponseRepository,
        QuestionTypeRepositoryInterface $questionTypeRepository,
        QuestionCategoryRepositoryInterface $questionCategoryRepository
    ) {
        $this->studentRepository = $studentRepository;
        $this->deployedQuestionnaireRepository = $deployedQuestionnaireRepository;
        $this->questionRepository = $questionRepository;
        $this->questionOptionsRepository = $questionOptionsRepository;
        $this->questionnaireTemplateRepository = $questionnaireTemplateRepository;
        $this->facultyRepository = $facultyRepository;
        $this->programRepository = $programRepository;
        $this->courseRepository = $courseRepository;
        $this->semesterRepository = $semesterRepository;
        $this->questionnaireTargetTypesRepository = $questionnaireTargetTypesRepository;
        $this->responseRepository = $responseRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->deployedQuestionRepository = $deployedQuestionRepository;
        $this->deployedQuestionOptionRepository = $deployedQuestionOptionRepository;
        $this->deployedQuestionnaireTargetRepository = $deployedQuestionnaireTargetRepository;
        $this->userRepository = $userRepository;
        $this->facultyMemberRepository = $facultyMemberRepository;
        $this->semesterCourseRepository = $semesterCourseRepository;
        $this->bgTaskLogRepository = $bgTaskLogRepository;
        $this->questionResponseRepository = $questionResponseRepository;
        $this->questionTypeRepository = $questionTypeRepository;
        $this->questionCategoryRepository = $questionCategoryRepository;
    }

    public function questionnaireTemplates(): QuestionnaireTemplateRepositoryInterface
    {
        return $this->questionnaireTemplateRepository;
    }

    public function questionnaires(): DeployedQuestionnaireRepositoryInterface
    {
        return $this->deployedQuestionnaireRepository;
    }

    public function deployedQuestionnaires(): DeployedQuestionnaireRepositoryInterface
    {
        return $this->deployedQuestionnaireRepository;
    }

    public function deployedQuestionnaireTargets(): DeployedQuestionnaireTargetRepositoryInterface
    {
        return $this->deployedQuestionnaireTargetRepository;
    }

    public function questionnaireTargetTypes(): QuestionnaireTargetTypesInterface
    {
        return $this->questionnaireTargetTypesRepository;
    }

    public function questions(): QuestionRepositoryInterface
    {
        return $this->questionRepository;
    }

    public function questionOptions(): QuestionOptionsRepositoryInterface
    {
        return $this->questionOptionsRepository;
    }

    public function deployedQuestions(): DeployedQuestionRepositoryInterface
    {
        return $this->deployedQuestionRepository;
    }

    public function deployedQuestionOption(): DeployedQuestionOptionRepositoryInterface
    {
        return $this->deployedQuestionOptionRepository;
    }

    public function faculties(): FacultyRepositoryInterface
    {
        return $this->facultyRepository;
    }

    public function programs(): ProgramRepositoryInterface
    {
        return $this->programRepository;
    }

    public function courses(): CourseRepositoryInterface
    {
        return $this->courseRepository;
    }

    public function semesters(): SemesterRepositoryInterface
    {
        return $this->semesterRepository;
    }

    public function students(): StudentRepositoryInterface
    {
        return $this->studentRepository;
    }

    public function facultyMembers(): FacultyMemberRepositoryInterface
    {
        return $this->facultyMemberRepository;
    }

    public function enrollments(): EnrollmentRepositoryInterface
    {
        return $this->enrollmentRepository;
    }

    public function responses(): ResponseRepositoryInterface
    {
        return $this->responseRepository;
    }

    public function users(): UserRepositoryInterface
    {
        return $this->userRepository;
    }

    public function semesterCourses(): SemesterCourseRepositoryInterface
    {
        return $this->semesterCourseRepository;
    }

    public function bgTaskLogs(): BgTaskLogRepositoryInterface
    {
        return $this->bgTaskLogRepository;
    }

    public function questionResponses(): QuestionResponseRepositoryInterface
    {
        return $this->questionResponseRepository;
    }

    public function questionTypes(): QuestionTypeRepositoryInterface
    {
        return $this->questionTypeRepository;
    }

    public function questionCategories(): QuestionCategoryRepositoryInterface
    {
        return $this->questionCategoryRepository;
    }

    public function beginTransaction(): void
    {
        DB::beginTransaction();
        $this->inTransaction = true;
    }

    public function endTransaction(): void
    {
        if ($this->inTransaction) {
            DB::commit();
            $this->inTransaction = false;
        }
    }

    public function commit(): void
    {
        if (!$this->inTransaction) {
            DB::beginTransaction();
            $this->inTransaction = true;
        }

        try {
            DB::commit();
            $this->inTransaction = false;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function rollback(): void
    {
        if ($this->inTransaction) {
            DB::rollBack();
            $this->inTransaction = false;
        }
    }
}