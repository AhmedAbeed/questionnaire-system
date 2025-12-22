<?php

namespace App\Repositories;

use App\Models\Student;
use App\Contracts\StudentRepositoryInterface;

class StudentRepository extends BaseRepository implements StudentRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return Student::class;
    }
}