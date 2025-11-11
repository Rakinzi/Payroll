<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if we're in tenant context
        if (Tenant::current()) {
            // Seeding tenant database
            $this->seedTenantDatabase();
        } else {
            // Seeding central database
            $this->seedCentralDatabase();
        }
    }

    /**
     * Seed the central database.
     */
    private function seedCentralDatabase(): void
    {
        $this->command->info('Seeding central database...');

        // Seed tenant records
        $this->call(TenantSeeder::class);

        $this->command->info('Central database seeded!');

        // Automatically run tenant migrations and seeding
        $this->command->newLine();
        $this->command->info('Running tenant migrations and seeding...');

        $this->command->call('tenants:migrate-fresh');
        $this->command->call('tenants:seed');

        $this->command->newLine();
        $this->command->info('✓ All databases migrated and seeded successfully!');
        $this->command->newLine();
        $this->command->warn('Default admin credentials for each tenant:');
        $this->command->line('  Email: admin@example.com');
        $this->command->line('  Password: password');
    }

    /**
     * Seed tenant database.
     */
    private function seedTenantDatabase(): void
    {
        $this->command->info('Seeding tenant database: ' . Tenant::current()->id);

        // Seed permissions and roles for this tenant
        $this->call(PermissionSeeder::class);

        // Seed cost centers
        $this->call(CostCenterSeeder::class);

        // Seed test users with roles
        $this->call(TestUserSeeder::class);

        $this->command->info('Tenant database seeded!');
    }
}
