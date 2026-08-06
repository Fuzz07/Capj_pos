<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'full_name' => 'Administrator',
                'email' => 'admincapj@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['username' => 'staff'],
            [
                'full_name' => 'Staff Member',
                'email' => 'staff@captainj.com',
                'password' => Hash::make('staff123'),
                'role' => 'staff',
                'email_verified_at' => now(),
            ]
        );
    }
}
