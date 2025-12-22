<?php

namespace App\Repositories;

use App\Contracts\CourseRepositoryInterface;
use App\Models\Course;

class CourseRepository extends BaseRepository implements CourseRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return Course::class;
    }
}
