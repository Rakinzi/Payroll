<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate
                            {tenant? : The tenant ID (optional, runs for all tenants if not provided)}
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Seed the database after migrations}
                            {--path= : The path to the migrations files}';

    protected $description = 'Run migrations for one or all tenants';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant');

        if ($tenantId) {
            return $this->migrateTenant($tenantId);
        }

        // Migrate all tenants
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $this->info("Migrating {$tenants->count()} tenant(s)...");
        $this->newLine();

        foreach ($tenants as $tenant) {
            $this->migrateTenant($tenant->id);
            $this->newLine();
        }

        $this->info('✓ All tenants migrated successfully!');

        return self::SUCCESS;
    }

    private function migrateTenant(string $tenantId): int
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("Tenant '{$tenantId}' not found!");
            return self::FAILURE;
        }

        $this->info("Migrating tenant: {$tenant->id} ({$tenant->name})");

        $tenant->makeCurrent();

        $options = [
            '--database' => 'tenant',
            '--force' => true,
        ];

        if ($this->option('fresh')) {
            $options['--drop-views'] = true;
            Artisan::call('migrate:fresh', $options, $this->output);
        } else {
            if ($path = $this->option('path')) {
                $options['--path'] = $path;
            }
            Artisan::call('migrate', $options, $this->output);
        }

        if ($this->option('seed')) {
            Artisan::call('db:seed', [
                '--database' => 'tenant',
                '--force' => true,
            ], $this->output);
        }

        $tenant->forgetCurrent();

        $this->info("✓ Tenant '{$tenant->id}' migrated successfully");

        return self::SUCCESS;
    }
}
