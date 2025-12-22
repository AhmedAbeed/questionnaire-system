<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use App\Helpers\helpers;

class InstructorEnrollmentImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $instructorEnrollments;
    protected $rowNumber;

    public function __construct()
    {
        $this->instructorEnrollments = collect();
        $this->rowNumber = 2;
    }

    public function collection(Collection $rows)
    {
        try {
            $rows->each(function ($row) {
                $this->instructorEnrollments->push(array_merge($row->toArray(), [
                    'row_number' => $this->rowNumber++
                ]));
            });
        } catch (\Exception $e) {
            logErrorImport('Failed to process instructor enrollment import', 'InstructorEnrollmentImport', $e, [
                'row_number' => $this->rowNumber,
                'total_rows' => $rows->count()
            ]);
            throw $e;
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function getInstructorEnrollments(): Collection
    {
        return $this->instructorEnrollments;
    }
}