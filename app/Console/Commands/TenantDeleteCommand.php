<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantDeleteCommand extends Command
{
    protected $signature = 'tenant:delete
                            {tenant : The tenant ID}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Delete a tenant and its database';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant');

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("Tenant '{$tenantId}' not found!");
            return self::FAILURE;
        }

        $this->warn("You are about to delete tenant: {$tenant->id} ({$tenant->name})");
        $this->warn("Database: {$tenant->database}");
        $this->warn("This action cannot be undone!");

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Deletion cancelled.');
                return self::SUCCESS;
            }
        }

        $database = $tenant->database;

        // Delete domains
        $domainCount = $tenant->domains()->count();
        $tenant->domains()->delete();
        $this->info("✓ Deleted {$domainCount} domain(s)");

        // Delete the database
        $this->deleteDatabase($database);
        $this->info("✓ Database '{$database}' deleted");

        // Delete the tenant
        $tenant->delete();
        $this->info("✓ Tenant '{$tenantId}' deleted");

        $this->newLine();
        $this->info("🗑️  Tenant '{$tenantId}' and all associated data have been deleted.");

        return self::SUCCESS;
    }

    private function deleteDatabase(string $database): void
    {
        $connection = Config::get('database.default');
        $driver = Config::get("database.connections.{$connection}.driver");

        try {
            if ($driver === 'mysql') {
                DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$database}`");
            } elseif ($driver === 'pgsql') {
                DB::connection('central')->statement("DROP DATABASE IF EXISTS \"{$database}\"");
            } elseif ($driver === 'sqlite') {
                $path = database_path("{$database}.sqlite");
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        } catch (\Exception $e) {
            $this->warn("Could not delete database: {$e->getMessage()}");
        }
    }
}
