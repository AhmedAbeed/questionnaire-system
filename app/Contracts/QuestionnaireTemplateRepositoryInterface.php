<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface QuestionnaireTemplateRepositoryInterface extends RepositoryInterface
{
    public function getActiveQuestionnaireTemplates(): Collection;
    public function getQuestionnaireTemplate(int $templateId);
}