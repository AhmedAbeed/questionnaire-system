<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'questionnaire@nmu.edu.eg',
            'full_name' => 'Super Admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $user->assignRole(UserRole::ADMIN->value);
    }
}
