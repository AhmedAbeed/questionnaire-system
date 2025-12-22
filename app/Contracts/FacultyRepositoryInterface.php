<?php

namespace App\Contracts;

use App\Models\Faculty;

interface FacultyRepositoryInterface extends RepositoryInterface
{
    /**
     * Get faculty by dean ID
     * 
     * @param int $deanId The ID of the dean
     * @return Faculty|null The faculty or null if not found
     */
    public function getFacultyByDeanId(int $deanId): ?Faculty;
}