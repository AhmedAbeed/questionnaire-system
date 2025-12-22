<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;
use App\Exceptions\ServiceException;
use App\Exceptions\BusinessValidationException;

class SemesterService extends BaseService
{
    /**
     * Get all semesters
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        try {
            return $this->unitOfWork->semesters()->all();
        } catch (BusinessValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            logError('Failed to retrieve all semesters', 'SemesterService', $e);
            throw new ServiceException('Unable to retrieve semesters due to system error', 0, $e);
        }
    }
}