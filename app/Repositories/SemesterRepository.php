<?php

namespace App\Repositories;

use App\Models\Semester;
use App\Contracts\SemesterRepositoryInterface;

class SemesterRepository extends BaseRepository implements SemesterRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return Semester::class;
    }
}
