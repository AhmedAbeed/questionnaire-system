<?php

namespace App\Repositories;

use App\Models\FacultyMember;
use App\Contracts\FacultyMemberRepositoryInterface;

class FacultyMemberRepository extends BaseRepository implements FacultyMemberRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return FacultyMember::class;
    }
}