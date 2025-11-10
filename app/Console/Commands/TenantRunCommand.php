<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantRunCommand extends Command
{
    protected $signature = 'tenant:run
                            {tenant : The tenant ID}
                            {artisan_command : The artisan command to run}
                            {--option=* : Options to pass to the command}
                            {--argument=* : Arguments to pass to the command}';

    protected $description = 'Run an artisan command in the context of a tenant';

    public function handle(): int
    {
        $tenantId = $this->argument('tenant');
        $command = $this->argument('artisan_command');

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            $this->error("Tenant '{$tenantId}' not found!");
            return self::FAILURE;
        }

        $this->info("Running '{$command}' for tenant: {$tenant->id} ({$tenant->name})");
        $this->newLine();

        $tenant->makeCurrent();

        $parameters = $this->buildParameters();

        Artisan::call($command, $parameters, $this->output);

        $tenant->forgetCurrent();

        $this->newLine();
        $this->info("✓ Command executed successfully");

        return self::SUCCESS;
    }

    private function buildParameters(): array
    {
        $parameters = [];

        // Add options
        foreach ($this->option('option') as $option) {
            if (str_contains($option, '=')) {
                [$key, $value] = explode('=', $option, 2);
                $parameters["--{$key}"] = $value;
            } else {
                $parameters["--{$option}"] = true;
            }
        }

        // Add arguments
        foreach ($this->option('argument') as $argument) {
            if (str_contains($argument, '=')) {
                [$key, $value] = explode('=', $argument, 2);
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
