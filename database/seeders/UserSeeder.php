<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@mabini.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create manager user
        User::create([
            'name' => 'Manager',
            'email' => 'manager@mabini.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        // Create staff user
        User::create([
            'name' => 'Staff',
            'email' => 'staff@mabini.com',
            'password' => Hash::make('password'),
            'role' => 'staff',
        ]);
    }
}
