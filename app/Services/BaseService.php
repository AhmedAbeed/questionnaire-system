<?php

namespace App\Services;

use App\Contracts\UnitOfWorkInterface;

abstract class BaseService
{
    protected $unitOfWork;

    public function __construct(UnitOfWorkInterface $unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
    }
}
