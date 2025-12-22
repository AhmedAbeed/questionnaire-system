<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;

class CourseQuestionnaireSummaryExport implements FromCollection, WithEvents
{
    protected $summary;

    public function __construct($summary)
    {
        $this->summary = $summary;
    }

    public function collection(): Collection
    {
        $optionTexts = $this->extractOptionTexts();
        $rows = [];
        
        // Header row: Question, Option1, Option2, ..., Average, Scale
        $rows[] = $this->buildHeaderRow($optionTexts);
        
        foreach ($this->summary as $questionSummary) {
            $questionRows = $this->buildDataRows($questionSummary, $optionTexts);
            foreach ($questionRows as $row) {
                $rows[] = $row;
            }
        }

        return new Collection($rows);
    }

    private function extractOptionTexts(): array
    {
        $firstQuestion = reset($this->summary);
        
        if (!$firstQuestion || !isset($firstQuestion['stats'])) {
            return [];
        }

        $optionTexts = [];

        foreach ($firstQuestion['stats'] as $stat) {
            $optionTexts[] = $this->getOptionText($stat['option']);
        }

        return $optionTexts;
    }

    private function getOptionText($option): string
    {
        return $option->option_text;
    }

    private function buildHeaderRow(array $optionTexts): array
    {
        return array_merge(['Question'], $optionTexts, ['Average']);
    }

    private function buildDataRows(array $questionSummary, array $optionTexts): array
    {
        $question = $questionSummary['question'];
        $optionStats = $this->mapOptionStats($questionSummary['stats']);

        // First row: counts + average value
        $countRow = [$question->getText()];
        foreach ($optionTexts as $optionText) {
            $stat = $optionStats[$optionText] ?? ['count' => 0, 'percentage' => 0.0];
            $countRow[] = $stat['count'];
        }
        $average = $questionSummary['average'] ?? '';
        $countRow[] = $average;

        // Second row: percentages + scale text
        $percentageRow = [''];
        foreach ($optionTexts as $optionText) {
            $stat = $optionStats[$optionText] ?? ['count' => 0, 'percentage' => 0.0];
            $percentageRow[] = $stat['percentage'] . '%';
        }
        $percentageRow[] = $this->getScaleText($average);

        return [$countRow, $percentageRow];
    }

    private function mapOptionStats(array $stats): array
    {
        $optionStats = [];
        
        foreach ($stats as $stat) {
            $optionText = $this->getOptionText($stat['option']);
            $optionStats[$optionText] = [
                'count' => $stat['count'],
                'percentage' => $stat['percentage']
            ];
        }

        return $optionStats;
    }

    private function getScaleText($average): string
    {
        if ($average === null || $average === '') {
            return '';
        }

        $scaleMap = [
            2 => 'ضعيف.',
            3 => 'مقبول.',
            4 => 'جيد.',
            5 => 'جيد جداً.',
        ];

        foreach ($scaleMap as $threshold => $text) {
            if ($average < $threshold) {
                return $text;
            }
        }

        return 'ممتاز.';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = count($this->summary);
                $startRow = 2; // Start after header row
                
                // Set RTL for the entire sheet
                $sheet->setRightToLeft(true);
                
                // Merge question cells
                for ($i = 0; $i < $rowCount; $i++) {
                    $row1 = $startRow + ($i * 2);     // Count row
                    $row2 = $row1 + 1;                // Percentage row
                    
                    // Merge the question cell (column A) for both rows
                    $sheet->mergeCells("A{$row1}:A{$row2}");
                }
                
                // Optional: Set text alignment for better RTL display
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                      ->getAlignment()
                      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            },
        ];
    }
}