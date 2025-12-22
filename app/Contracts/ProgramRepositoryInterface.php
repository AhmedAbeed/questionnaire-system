<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface ProgramRepositoryInterface extends RepositoryInterface
{
    /**
     * Find programs by faculty ID
     * 
     * @param int $facultyId The faculty ID
     * @return Collection The collection of programs
     */
    public function findByFacultyId(int $facultyId): Collection;
}