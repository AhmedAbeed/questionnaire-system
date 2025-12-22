<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class EnrollmentsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $enrollments;
    protected $rowNumber;

    public function __construct()
    {
        $this->enrollments = collect();
        $this->rowNumber = 2;
    }

    public function collection(Collection $rows)
    {
        try {
            $rows->each(function ($row) {
                $this->enrollments->push(array_merge($row->toArray(), [
                    'row_number' => $this->rowNumber++
                ]));
            });
        } catch (\Exception $e) {
            logErrorImport('Failed to process enrollment import', 'EnrollmentsImport', $e, [
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

    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }
}