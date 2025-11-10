<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $costCenters = [
            [
                'center_name' => 'Administration',
                'center_code' => 'ADMIN',
                'description' => 'General administrative and support functions',
                'is_active' => true,
            ],
            [
                'center_name' => 'Sales',
                'center_code' => 'SALES',
                'description' => 'Sales department and business development',
                'is_active' => true,
            ],
            [
                'center_name' => 'Marketing',
                'center_code' => 'MKTG',
                'description' => 'Marketing and promotional activities',
                'is_active' => true,
            ],
            [
                'center_name' => 'Operations',
                'center_code' => 'OPS',
                'description' => 'Operational activities and production',
                'is_active' => true,
            ],
            [
                'center_name' => 'Finance',
                'center_code' => 'FIN',
                'description' => 'Financial management and accounting',
                'is_active' => true,
            ],
            [
                'center_name' => 'Human Resources',
                'center_code' => 'HR',
                'description' => 'Human resources and employee management',
                'is_active' => true,
            ],
            [
                'center_name' => 'IT Department',
                'center_code' => 'IT',
                'description' => 'Information technology and technical support',
                'is_active' => true,
            ],
            [
                'center_name' => 'Customer Service',
                'center_code' => 'CS',
                'description' => 'Customer support and service operations',
                'is_active' => true,
            ],
            [
                'center_name' => 'Research & Development',
                'center_code' => 'RND',
                'description' => 'Research, development, and innovation',
                'is_active' => true,
            ],
            [
                'center_name' => 'Logistics',
                'center_code' => 'LOG',
                'description' => 'Supply chain and logistics management',
                'is_active' => true,
            ],
        ];

        foreach ($costCenters as $costCenter) {
            CostCenter::firstOrCreate(
                ['center_code' => $costCenter['center_code']],
                $costCenter
            );
        }

        $this->command->info('Cost centers seeded successfully!');
    }
}
