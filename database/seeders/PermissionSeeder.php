<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Employee permissions
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',

            // Payroll permissions
            'view payroll',
            'create payroll',
            'edit payroll',
            'delete payroll',
            'process payroll',
            'approve payroll',

            // Leave permissions
            'view leaves',
            'create leaves',
            'edit leaves',
            'delete leaves',
            'approve leaves',

            // Report permissions
            'view reports',
            'export reports',

            // Cost center permissions
            'view cost centers',
            'create cost centers',
            'edit cost centers',
            'delete cost centers',

            // User management permissions
            'view users',
            'create users',
            'edit users',
            'delete users',

            // System settings permissions
            'view settings',
            'edit settings',

            // Special permission for super admin
            'access all centers',
        ];

        foreach ($permissions as $permission) {
            Permission::on('tenant')->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::on('tenant')->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::on('tenant')->get());

        // Admin - has most permissions except system-wide ones
        $admin = Role::on('tenant')->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'view payroll',
            'create payroll',
            'edit payroll',
            'delete payroll',
            'process payroll',
            'approve payroll',
            'view leaves',
            'create leaves',
            'edit leaves',
            'delete leaves',
            'approve leaves',
            'view reports',
            'export reports',
            'view users',
            'create users',
            'edit users',
        ]);

        // Payroll Manager - payroll focused
        $payrollManager = Role::on('tenant')->firstOrCreate(['name' => 'payroll-manager', 'guard_name' => 'web']);
        $payrollManager->syncPermissions([
            'view employees',
            'view payroll',
            'create payroll',
            'edit payroll',
            'process payroll',
            'approve payroll',
            'view reports',
            'export reports',
        ]);

        // HR Manager - employee and leave focused
        $hrManager = Role::on('tenant')->firstOrCreate(['name' => 'hr-manager', 'guard_name' => 'web']);
        $hrManager->syncPermissions([
            'view employees',
            'create employees',
            'edit employees',
            'view leaves',
            'create leaves',
            'edit leaves',
            'approve leaves',
            'view reports',
            'export reports',
        ]);

        // Employee - limited self-service permissions
        $employee = Role::on('tenant')->firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employee->syncPermissions([
            'view employees', // Limited to own data via policy
            'create leaves',   // For own leave requests
            'view leaves',     // For own leaves
        ]);
    }
}
