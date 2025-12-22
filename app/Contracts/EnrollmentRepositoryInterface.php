<?php

namespace App\Contracts;

use App\Models\Enrollment;

interface EnrollmentRepositoryInterface extends RepositoryInterface
{
    /**
     * Create a single enrollment record
     * 
     * @param array $data The enrollment data
     * @return Enrollment The created enrollment
     */
    public function createSingleEnrollment(array $data): Enrollment;
}