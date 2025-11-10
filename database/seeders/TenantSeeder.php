<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Seed the existing tenants into the central database.
     *
     * This seeder creates tenant records for all existing client databases.
     */
    public function run(): void
    {
        // Array of existing tenants from the current PHP implementation
        $tenants = [
            [
                'id' => 'local',
                'database' => 'lorimakp_lorimak_v1',
                'domain' => 'local.localhost',
                'system_name' => 'Lorimak',
                'logo' => null,
            ],
            [
                'id' => 'nhaka',
                'database' => 'lorimakp_nhaka',
                'domain' => 'nhaka.lorimakpayport.com',
                'system_name' => 'Lorimak',
                'logo' => null,
            ],
            [
                'id' => 'clary',
                'database' => 'lorimakp_clary',
                'domain' => 'clary.lorimakpayport.com',
                'system_name' => 'Clary Sage Travel',
                'logo' => 'assets/images/logo-clarysage.png',
            ],
            // Add more tenants as needed
            // [
            //     'id' => 'tenant-id',
            //     'database' => 'lorimakp_database_name',
            //     'domain' => 'subdomain.lorimakpayport.com',
            //     'system_name' => 'Company Name',
            //     'logo' => 'path/to/logo.png',
            // ],
        ];

        foreach ($tenants as $tenantData) {
            // Create tenant
            $tenant = Tenant::create([
                'id' => $tenantData['id'],
                'tenancy_db_name' => $tenantData['database'],
                'tenancy_db_driver' => 'mysql',
            ]);

            // Set system name and logo
            $tenant->withSystemName($tenantData['system_name']);

            if ($tenantData['logo']) {
                $tenant->withLogo($tenantData['logo']);
            }

            $tenant->save();

            // Create domain
            $tenant->domains()->create([
                'domain' => $tenantData['domain'],
            ]);

            $this->command->info("Created tenant: {$tenantData['system_name']} ({$tenantData['domain']})");
        }

        $this->command->info('All tenants seeded successfully!');
    }
}
