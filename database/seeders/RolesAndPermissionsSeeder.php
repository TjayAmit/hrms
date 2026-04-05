<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Reset cached roles and permissions
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Create permissions
            $permissions = [
                // User Management
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'users.manage-roles',
                
                // Employee Data
                'employees.view',
                'employees.create',
                'employees.edit',
                'employees.delete',
                'employees.view-salary',
                'employees.edit-salary',
                
                // Payroll
                'payroll.view',
                'payroll.process',
                'payroll.approve',
                'payroll.export',
                
                // Attendance
                'attendance.view',
                'attendance.mark',
                'attendance.edit',
                'attendance.approve',
                'attendance.reports',
                
                // Leave Management
                'leave.view',
                'leave.request',
                'leave.approve',
                'leave.reject',
                'leave.manage-types',
                
                // Reports
                'reports.view',
                'reports.export',
                'reports.advanced',
                
                // System Administration
                'system.settings',
                'system.audit',
                'system.backup',
                'system.maintenance',
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }

            // Create roles and assign permissions

            // Owner - Full system access
            $ownerRole = Role::firstOrCreate(['name' => 'Owner']);
            $ownerRole->givePermissionTo(Permission::all());

            // Manager - Department-level access
            $managerRole = Role::firstOrCreate(['name' => 'Manager']);
            $managerRole->givePermissionTo([
                'employees.view',
                'employees.create',
                'employees.edit',
                'employees.view-salary',
                'payroll.view',
                'payroll.approve',
                'attendance.view',
                'attendance.approve',
                'attendance.reports',
                'leave.view',
                'leave.approve',
                'leave.reject',
                'reports.view',
                'reports.export',
            ]);

            // Employee Administrator - Employee management
            $employeeAdminRole = Role::firstOrCreate(['name' => 'Employee Administrator']);
            $employeeAdminRole->givePermissionTo([
                'users.view',
                'users.create',
                'users.edit',
                'employees.view',
                'employees.create',
                'employees.edit',
                'employees.view-salary',
                'payroll.view',
                'payroll.process',
                'attendance.view',
                'attendance.mark',
                'attendance.edit',
                'leave.view',
                'leave.manage-types',
                'reports.view',
                'reports.export',
            ]);

            // Employee - Basic access
            $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
            $employeeRole->givePermissionTo([
                'employees.view',
                'attendance.view',
                'attendance.mark',
                'leave.view',
                'leave.request',
                'reports.view',
            ]);

            $this->command->info('Roles and permissions created successfully.');
        });
    }
}
