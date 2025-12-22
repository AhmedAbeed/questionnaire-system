<?php

namespace App\Repositories;

use App\Contracts\ProgramRepositoryInterface;
use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class ProgramRepository extends BaseRepository implements ProgramRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return Program::class;
    }

    /**
     * Find programs by faculty ID
     * 
     * @param int $facultyId The faculty ID
     * @return Collection The collection of programs
     * @throws Exception When retrieval fails
     */
    public function findByFacultyId(int $facultyId): Collection
    {
        try {
            return $this->query()
                ->where('faculty_id', $facultyId)
                ->select('id', 'name')
                ->get();
        } catch (Exception $e) {
            logError('Failed to find programs by faculty ID', $this->getRepositoryContext(), $e, ['faculty_id' => $facultyId]);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
