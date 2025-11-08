<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has most permissions except system-wide ones
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
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
        $payrollManager = Role::create(['name' => 'payroll-manager']);
        $payrollManager->givePermissionTo([
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
        $hrManager = Role::create(['name' => 'hr-manager']);
        $hrManager->givePermissionTo([
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
        $employee = Role::create(['name' => 'employee']);
        $employee->givePermissionTo([
            'view employees', // Limited to own data via policy
            'create leaves',   // For own leave requests
            'view leaves',     // For own leaves
        ]);
    }
}
