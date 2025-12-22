<?php

namespace App\Repositories;

use App\Contracts\EnrollmentRepositoryInterface;
use App\Models\Enrollment;

class EnrollmentRepository extends BaseRepository implements EnrollmentRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return Enrollment::class;
    }

    /**
     * Create a single enrollment record
     * 
     * @param array $data The enrollment data
     * @return Enrollment The created enrollment
     */
    public function createSingleEnrollment(array $data): Enrollment
    {
        return $this->create($data);
    }

}
