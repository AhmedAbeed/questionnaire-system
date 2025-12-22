<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QuestionnaireTargetType;

class QuestionnaireTargetTypeSeeder extends Seeder
{
    public function run()
    {
        QuestionnaireTargetType::create(['name' => 'student', 'scope' => 'academic']);
        QuestionnaireTargetType::create(['name' => 'lecturer', 'scope' => 'academic']);

        
    }
}
