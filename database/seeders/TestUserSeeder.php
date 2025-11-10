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
        // Create cost center
        $center = CostCenter::firstOrCreate(
            ['center_code' => 'HQ'],
            [
                'id' => Str::uuid(),
                'center_name' => 'Headquarters',
            ]
        );

        $this->command->info("Cost Center: {$center->center_name}");

        // Create test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Test User',
                'password' => Hash::make('password123'),
                'center_id' => $center->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("User created: {$user->email}");
        $this->command->info("Password: password123");
        $this->command->info("Cost Center: {$center->center_name} ({$center->center_code})");
    }
}
