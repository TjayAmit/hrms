<?php

use App\Services\RoleAssignmentService;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Role Assignment Service Extended Tests

it('creates new role successfully', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $service = new RoleAssignmentService();
    
    $role = $service->createRole('Test Role');
    
    expect($role)->toBeInstanceOf(Role::class);
    expect($role->name)->toBe('Test Role');
    expect(Role::where('name', 'Test Role')->exists())->toBeTrue();
});

it('creates new role with permissions', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $service = new RoleAssignmentService();
    
    // Create some permissions first
    $permission1 = Permission::create(['name' => 'test.permission1']);
    $permission2 = Permission::create(['name' => 'test.permission2']);
    
    $role = $service->createRole('Test Role With Permissions', [$permission1->name, $permission2->name]);
    
    expect($role->hasPermissionTo('test.permission1'))->toBeTrue();
    expect($role->hasPermissionTo('test.permission2'))->toBeTrue();
});

it('deletes role successfully', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $service = new RoleAssignmentService();
    
    $role = $service->createRole('Role To Delete');
    expect(Role::where('name', 'Role To Delete')->exists())->toBeTrue();
    
    $service->deleteRole('Role To Delete');
    
    expect(Role::where('name', 'Role To Delete')->exists())->toBeFalse();
});

it('deletes role and reassigns users to fallback role', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    User::query()->delete();
    
    $service = new RoleAssignmentService();
    
    // Create roles
    $roleToDelete = $service->createRole('Role To Delete');
    $fallbackRole = $service->createRole('Fallback Role');
    
    // Create user and assign to role to be deleted
    $user = User::factory()->create();
    $user->assignRole('Role To Delete');
    
    expect($user->hasRole('Role To Delete'))->toBeTrue();
    expect($user->hasRole('Fallback Role'))->toBeFalse();
    
    // Delete role with fallback
    $service->deleteRole('Role To Delete', 'Fallback Role');
    
    expect(Role::where('name', 'Role To Delete')->exists())->toBeFalse();
    expect($user->fresh()->hasRole('Fallback Role'))->toBeTrue();
});

it('gets all roles ordered by name', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $service = new RoleAssignmentService();
    
    // Create roles in random order
    $service->createRole('Zebra Role');
    $service->createRole('Alpha Role');
    $service->createRole('Beta Role');
    
    $roles = $service->getAllRoles();
    
    expect($roles)->toHaveCount(3);
    expect($roles->pluck('name')->toArray())->toBe(['Alpha Role', 'Beta Role', 'Zebra Role']);
});

it('gets permissions grouped by category correctly', function () {
    // Clean up any existing data first but don't delete existing roles/permissions
    Permission::query()->delete();
    
    $service = new RoleAssignmentService();
    
    // Create permissions with different categories
    Permission::create(['name' => 'users.view']);
    Permission::create(['name' => 'users.create']);
    Permission::create(['name' => 'employees.view']);
    Permission::create(['name' => 'employees.edit']);
    Permission::create(['name' => 'reports.view']);
    
    $permissionsByCategory = $service->getPermissionsByCategory();
    
    expect($permissionsByCategory)->toHaveKey('users');
    expect($permissionsByCategory)->toHaveKey('employees');
    expect($permissionsByCategory)->toHaveKey('reports');
    
    expect($permissionsByCategory['users'])->toHaveCount(2);
    expect($permissionsByCategory['employees'])->toHaveCount(2);
    expect($permissionsByCategory['reports'])->toHaveCount(1);
});

it('checks if user has any of specified roles', function () {
    // Clean up and seed roles first
    Role::query()->delete();
    Permission::query()->delete();
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $service = new RoleAssignmentService();
    
    $user = User::factory()->create();
    $user->assignRole('Employee');
    
    expect($service->userHasAnyRole($user, ['Manager', 'Employee']))->toBeTrue();
    expect($service->userHasAnyRole($user, ['Manager', 'Owner']))->toBeFalse();
});

it('syncs permissions correctly', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $service = new RoleAssignmentService();
    
    $user = User::factory()->create();
    
    // Create permissions
    $permission1 = Permission::create(['name' => 'test.sync1']);
    $permission2 = Permission::create(['name' => 'test.sync2']);
    $permission3 = Permission::create(['name' => 'test.sync3']);
    
    // Give user initial permissions
    $user->givePermissionTo([$permission1->name, $permission2->name]);
    expect($user->hasPermissionTo('test.sync1'))->toBeTrue();
    expect($user->hasPermissionTo('test.sync2'))->toBeTrue();
    expect($user->hasPermissionTo('test.sync3'))->toBeFalse();
    
    // Sync to different permissions
    $service->syncPermissions($user, [$permission2->name, $permission3->name]);
    
    $user->refresh();
    expect($user->hasPermissionTo('test.sync1'))->toBeFalse();
    expect($user->hasPermissionTo('test.sync2'))->toBeTrue();
    expect($user->hasPermissionTo('test.sync3'))->toBeTrue();
});
