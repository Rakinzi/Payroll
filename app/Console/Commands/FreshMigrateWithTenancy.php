<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FreshMigrateWithTenancy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:fresh-tenancy
                            {--seed : Seed the database after migration}
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all tables and re-run migrations for both central and tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting fresh migration for central and all tenant databases...');
        $this->newLine();

        // Confirm if in production
        if (!$this->option('force') && app()->environment('production')) {
            if (!$this->confirm('⚠️  You are in PRODUCTION! This will DELETE ALL DATA. Are you sure?')) {
                $this->error('Migration cancelled.');
                return Command::FAILURE;
            }
        }

        // Step 1: Fresh migrate central database
        $this->info('📊 Step 1: Migrating central database...');
        $this->call('migrate:fresh', [
            '--database' => 'central',
            '--force' => $this->option('force'),
            '--seed' => $this->option('seed'),
        ]);
        $this->info('✅ Central database migrated' . ($this->option('seed') ? ' and seeded' : ''));
        $this->newLine();

        // Step 2: Fresh migrate all tenant databases
        $this->info('🔄 Step 2: Migrating all tenant databases...');
        $params = ['--force' => $this->option('force')];

        if ($this->option('seed')) {
            $params['--seed'] = true;
        }

        $this->call('tenants:migrate-fresh', $params);
        $this->info('✅ All tenant databases migrated' . ($this->option('seed') ? ' and seeded' : ''));
        $this->newLine();

        $this->info('🎉 Fresh migration complete for central and all tenant databases!');
        $this->newLine();

        if ($this->option('seed')) {
            $this->info('💡 Default admin credentials (each tenant):');
            $this->info('   Email: admin@example.com');
            $this->info('   Password: password');
        }

        return Command::SUCCESS;
    }
}
