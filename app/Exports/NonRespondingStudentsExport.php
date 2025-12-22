<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Exception;

class NonRespondingStudentsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * @var array
     */
    protected array $data;

    /**
     * @var int
     */
    protected int $maxCourses = 0;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->calculateMaxCourses();
    }

    /**
     * Calculate the maximum number of courses for any student
     * 
     * @return void
     */
    protected function calculateMaxCourses(): void
    {
        $courseCounts = [];
        foreach ($this->data as $row) {
            $nationalId = $row['national_id'];
            if (!isset($courseCounts[$nationalId])) {
                $courseCounts[$nationalId] = 0;
            }
            $courseCounts[$nationalId]++;
        }
        $this->maxCourses = $courseCounts ? max($courseCounts) : 0;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        try {
            // Use array_reduce for more efficient grouping
            $groupedData = array_reduce($this->data, function ($carry, $row) {
                $nationalId = $row['national_id'];
                if (!isset($carry[$nationalId])) {
                    $carry[$nationalId] = [
                        'national_id' => $nationalId,
                        'academic_id' => $row['academic_id'] ?? '',
                        'student_name' => $row['name'] ?? '',
                        'courses' => []
                    ];
                }
                $courseName = $row['course'] ?? '';
                $courseCode = $row['course_code'] ?? '';
                $carry[$nationalId]['courses'][] = $courseCode ? "{$courseName} - {$courseCode}" : $courseName;
                return $carry;
            }, []);

            return collect($groupedData);
        } catch (Exception $e) {
            throw new Exception('Failed to process export data: ' . $e->getMessage());
        }
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $headers = ['National ID', 'Academic ID', 'Student Name'];
        for ($i = 1; $i <= $this->maxCourses; $i++) {
            $headers[] = "Course $i";
        }
        return $headers;
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        try {
            $result = [
                $row['national_id'],
                $row['academic_id'],
                $row['student_name']
            ];
            
            // Add all courses for this student
            $result = array_merge($result, $row['courses']);
            
            // Fill remaining columns with empty values if needed
            $remainingColumns = $this->maxCourses - count($row['courses']);
            if ($remainingColumns > 0) {
                $result = array_merge($result, array_fill(0, $remainingColumns, ''));
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception('Failed to map row data: ' . $e->getMessage());
        }
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
} 