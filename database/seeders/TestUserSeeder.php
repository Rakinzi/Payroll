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
        // Create first cost center/company
        $center1 = CostCenter::firstOrCreate(
            ['center_code' => 'TESTCO'],
            [
                'id' => Str::uuid(),
                'center_name' => 'Test Company Ltd',
                'is_active' => true,
            ]
        );

        $this->command->info("Cost Center 1: {$center1->center_name} ({$center1->center_code})");

        // Create second cost center/company
        $center2 = CostCenter::firstOrCreate(
            ['center_code' => 'DEMO'],
            [
                'id' => Str::uuid(),
                'center_name' => 'Demo Corporation',
                'is_active' => true,
            ]
        );

        $this->command->info("Cost Center 2: {$center2->center_name} ({$center2->center_code})");

        // Create super admin user (can access all cost centers)
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@lorimak.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'center_id' => null, // null means access to all cost centers
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Super Admin created: {$superAdmin->email} (Access: ALL COMPANIES)");

        // Create regular user for Test Company Ltd
        $user1 = User::firstOrCreate(
            ['email' => 'user@testcompany.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Test Company User',
                'password' => Hash::make('password123'),
                'center_id' => $center1->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("User 1 created: {$user1->email} (Company: {$center1->center_name})");

        // Create regular user for Demo Corporation
        $user2 = User::firstOrCreate(
            ['email' => 'user@democorp.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Demo Corp User',
                'password' => Hash::make('password123'),
                'center_id' => $center2->id,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("User 2 created: {$user2->email} (Company: {$center2->center_name})");

        $this->command->newLine();
        $this->command->info("=== Login Credentials ===");
        $this->command->info("Super Admin: admin@lorimak.com / password123 (All Companies)");
        $this->command->info("Test Co User: user@testcompany.com / password123 (Test Company Ltd only)");
        $this->command->info("Demo Corp User: user@democorp.com / password123 (Demo Corporation only)");
    }
}
