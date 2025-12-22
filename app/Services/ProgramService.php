<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class ProgramService extends BaseService
{
    /**
     * Get all programs
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        try {
            return $this->unitOfWork->programs()->all();
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to retrieve all programs', 'ProgramService', $e);
            throw new ServiceException('Unable to retrieve programs due to system error', 0, $e);
        }
    }

    /**
     * Find programs by faculty ID
     * 
     * @param int $facultyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function findByFacultyId(int $facultyId)
    {
        try {
            return $this->unitOfWork->programs()->findByFacultyId($facultyId);
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to find programs by faculty ID', 'ProgramService', $e);
            throw new ServiceException('Unable to find programs by faculty ID due to system error', 0, $e);
        }
    }
}