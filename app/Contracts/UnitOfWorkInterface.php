<?php

namespace App\Contracts;

use App\Contracts\StudentRepositoryInterface;
use App\Contracts\DeployedQuestionnaireRepositoryInterface;
use App\Contracts\QuestionnaireTemplateRepositoryInterface;
use App\Contracts\QuestionnaireTargetTypesInterface;
use App\Contracts\FacultyRepositoryInterface;
use App\Contracts\ProgramRepositoryInterface;
use App\Contracts\CourseRepositoryInterface;
use App\Contracts\SemesterRepositoryInterface;
use App\Contracts\ResponseRepositoryInterface;

interface UnitOfWorkInterface
{
    public function students(): \App\Contracts\StudentRepositoryInterface;
    public function questionnaires(): \App\Contracts\DeployedQuestionnaireRepositoryInterface;
    public function questionnaireTemplates(): \App\Contracts\QuestionnaireTemplateRepositoryInterface;
    public function questionnaireTargetTypes(): \App\Contracts\QuestionnaireTargetTypesInterface;
    public function faculties(): \App\Contracts\FacultyRepositoryInterface;
    public function programs(): \App\Contracts\ProgramRepositoryInterface;
    public function courses(): \App\Contracts\CourseRepositoryInterface;
    public function semesters(): \App\Contracts\SemesterRepositoryInterface;
    public function responses(): \App\Contracts\ResponseRepositoryInterface;
    public function commit(): void;
    public function rollback(): void;
}