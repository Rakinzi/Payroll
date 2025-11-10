<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantSeedCommand extends Command
{
    protected $signature = 'tenant:seed
                            {tenant? : The tenant ID (optional, runs for all tenants if not provided)}
                            {--class= : The seeder class to run}';

    protected $description = 'Seed the database for one or all tenants';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            return $this->seedTenant($tenantId);
        }

        // Seed all tenants
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $this->info("Seeding {$tenants->count()} tenant(s)...");
        $this->newLine();

        foreach ($tenants as $tenant) {
            $this->seedTenant($tenant->id);
            $this->newLine();
        }

        $this->info('✓ All tenants seeded successfully!');

        return self::SUCCESS;
    }

    private function seedTenant(string $tenantId): int
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("Tenant '{$tenantId}' not found!");
            return self::FAILURE;
        }

        $this->info("Seeding tenant: {$tenant->id} ({$tenant->name})");

        $tenant->makeCurrent();

        $options = [
            '--database' => 'tenant',
            '--force' => true,
        ];

        if ($class = $this->option('class')) {
            $options['--class'] = $class;
        }

        Artisan::call('db:seed', $options, $this->output);

        $tenant->forgetCurrent();

        $this->info("✓ Tenant '{$tenant->id}' seeded successfully");

        return self::SUCCESS;
    }
}
