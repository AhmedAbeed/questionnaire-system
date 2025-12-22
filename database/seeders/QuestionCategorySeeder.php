<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionCategory;

class QuestionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionCategories = [
            ['name' => 'Lecturer Performance'],
            ['name' => 'Course Content and Organization'],
            ['name' => 'Learning Environment and Equipment'],
            ['name' => 'Assessment and Examinations']
        ];

        foreach ($questionCategories as $category) {
            QuestionCategory::create($category);
        }
    }
}