<?php

namespace App\Repositories;

use App\Models\Faculty;
use App\Contracts\FacultyRepositoryInterface;
use Exception;

class FacultyRepository extends BaseRepository implements FacultyRepositoryInterface
{
    /**
     * Get the model class name
     * 
     * @return string
     */
    public function model(): string
    {
        return Faculty::class;
    }

    /**
     * Get faculty by dean ID
     * 
     * @param int $deanId The ID of the dean
     * @return Faculty|null The faculty or null if not found
     * @throws Exception When retrieval fails
     */
    public function getFacultyByDeanId(int $deanId): ?Faculty
    {
        try {
            return $this->model->where('dean_id', $deanId)->first();
        } catch (Exception $e) {
            logError('Failed to get faculty by dean ID', $this->getRepositoryContext(), $e, ['dean_id' => $deanId]);
            throw new Exception('Repository error: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}