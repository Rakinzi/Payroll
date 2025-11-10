<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TenantsMigrateFreshSeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate-fresh-seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables, re-run all migrations, and seed the database for tenant(s)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Running fresh migrations for tenants...');
        $this->call('tenants:migrate-fresh');

        $this->newLine();
        $this->info('Seeding tenant databases...');
        $this->call('tenants:seed');

        $this->newLine();
        $this->info('✓ Tenants migrated and seeded successfully!');
    }
}
