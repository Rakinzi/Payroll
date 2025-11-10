<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantListCommand extends Command
{
    protected $signature = 'tenant:list';

    protected $description = 'List all tenants';

    public function handle(): int
    {
        $tenants = Tenant::with('domains')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $rows = $tenants->map(function ($tenant) {
            return [
                $tenant->id,
                $tenant->name,
                $tenant->database,
                $tenant->domains->pluck('domain')->implode(', '),
                $tenant->created_at->format('Y-m-d H:i'),
            ];
        });

        $this->table(
            ['ID', 'Name', 'Database', 'Domains', 'Created'],
            $rows
        );

        $this->info("Total: {$tenants->count()} tenant(s)");

        return self::SUCCESS;
    }
}
