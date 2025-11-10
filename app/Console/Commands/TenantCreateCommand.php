<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantCreateCommand extends Command
{
    protected $signature = 'tenant:create
                            {id : The tenant ID}
                            {domain : The domain for this tenant}
                            {--db= : The database name (defaults to tenant ID)}
                            {--name= : The system name for the tenant}
                            {--migrate : Run migrations after creating the database}
                            {--seed : Seed the database after migrations}';

    protected $description = 'Create a new tenant with database and domain';

    public function handle(): int
    {
        $tenantId = $this->argument('id');
        $domain = $this->argument('domain');
        $database = $this->option('db') ?? $tenantId;
        $name = $this->option('name') ?? ucfirst($tenantId);

        $this->info("Creating tenant: {$tenantId}");

        // Check if tenant already exists
        if (Tenant::find($tenantId)) {
            $this->error("Tenant with ID '{$tenantId}' already exists!");
            return self::FAILURE;
        }

        // Check if domain already exists
        if (Domain::where('domain', $domain)->exists()) {
            $this->error("Domain '{$domain}' already exists!");
            return self::FAILURE;
        }

        // Create the tenant
        $tenant = Tenant::create([
            'id' => $tenantId,
            'database' => $database,
            'data' => [
                'system_name' => $name,
            ],
        ]);

        $this->info("✓ Tenant created");

        // Create the domain
        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => $domain,
        ]);

        $this->info("✓ Domain '{$domain}' created");

        // Create the database
        $this->createDatabase($database);
        $this->info("✓ Database '{$database}' created");

        // Run migrations if requested
        if ($this->option('migrate')) {
            $this->info("Running migrations...");
            $this->call('tenant:migrate', ['tenant' => $tenantId]);
        }

        // Seed if requested
        if ($this->option('seed')) {
            $this->info("Seeding database...");
            $this->call('tenant:seed', ['tenant' => $tenantId]);
        }

        $this->newLine();
        $this->info("🎉 Tenant '{$tenantId}' created successfully!");
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $tenant->id],
                ['Name', $tenant->system_name],
                ['Domain', $domain],
                ['Database', $database],
            ]
        );

        return self::SUCCESS;
    }

    private function createDatabase(string $database): void
    {
        $connection = Config::get('database.default');
        $driver = Config::get("database.connections.{$connection}.driver");

        if ($driver === 'mysql') {
            DB::connection('central')->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } elseif ($driver === 'pgsql') {
            DB::connection('central')->statement("CREATE DATABASE \"{$database}\" WITH ENCODING 'UTF8'");
        } elseif ($driver === 'sqlite') {
            touch(database_path("{$database}.sqlite"));
        }
    }
}
