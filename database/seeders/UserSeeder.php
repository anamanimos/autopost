<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Default Administrator Account
        User::firstOrCreate(
            ['email' => 'admin@sosmedauto.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
                'phone' => '081234567890',
            ]
        );

        // 2. Default Operator Account
        User::firstOrCreate(
            ['email' => 'operator@sosmedauto.com'],
            [
                'name' => 'Operator Media',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'status' => 'active',
                'phone' => '081234567891',
            ]
        );
    }
}
