<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create cost center (represents a company/organization)
        $center = CostCenter::firstOrCreate(
            ['center_code' => 'TESTCO'],
            [
                'id' => Str::uuid(),
                'center_name' => 'Test Company Ltd',
            ]
        );

        $this->command->info("Cost Center (Company): {$center->center_name}");

        // Create test user for this company
        $user = User::firstOrCreate(
            ['email' => 'admin@testcompany.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'center_id' => $center->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("User created: {$user->email}");
        $this->command->info("Password: password123");
        $this->command->info("Company: {$center->center_name} (Code: {$center->center_code})");
    }
}
