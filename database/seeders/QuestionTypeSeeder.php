<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionType;

class QuestionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionTypes = [
            [
                'name' => 'Single Choice',
                'has_options' => true,
            ],
            [
                'name' => 'Multiple Choice',
                'has_options' => true,
            ],
            [
                'name' => 'Text',
                'has_options' => false,
            ],
            [
                'name' => 'Likert Scale',
                'has_options' => true,
            ],
            [
                'name' => 'Date',
                'has_options' => false,
            ],
            [
                'name' => 'Time',
                'has_options' => false,
            ],
            [
                'name' => 'File',
                'has_options' => false,
            ],
            [
                'name' => 'Image',
                'has_options' => false,
            ],
            [
                'name' => "Instructor Select",
                'has_options' => true,
            ]
        ];

        foreach ($questionTypes as $type) {
            QuestionType::create($type);
        }
    }
} 