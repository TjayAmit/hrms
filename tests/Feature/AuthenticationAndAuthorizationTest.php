<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\RoleAssignmentService;

// Authentication Tests

it('requires email verification for protected routes', function () {
    $user = User::factory()->unverified()->create();
    
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect('/email/verify');
});

it('allows verified users to access protected routes', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful();
});

it('registers user with email verification required', function () {
    Notification::fake();
    
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    
    $response->assertStatus(302);
    
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->toBeNull();
});

it('sends email verification notification upon registration', function () {
    Notification::fake();
    
    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    
    $user = User::where('email', 'test2@example.com')->first();
    
    // Check if the notification was sent to the created user
    Notification::assertSentTo(
        $user,
        \Illuminate\Auth\Notifications\VerifyEmail::class
    );
});

// Role and Permission Tests

it('creates roles and permissions seeder correctly', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    // Check if all roles were created
    $expectedRoles = ['Owner', 'Manager', 'Employee Administrator', 'Employee'];
    foreach ($expectedRoles as $roleName) {
        expect(Role::where('name', $roleName)->exists())->toBeTrue();
    }
    
    // Check if all permissions were created
    $expectedPermissions = [
        'users.view', 'users.create', 'users.edit', 'users.delete',
        'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
        'payroll.view', 'payroll.process', 'attendance.view', 'attendance.mark',
        'leave.view', 'leave.request', 'reports.view', 'reports.export'
    ];
    
    foreach ($expectedPermissions as $permissionName) {
        expect(Permission::where('name', $permissionName)->exists())->toBeTrue();
    }
});

it('assigns correct permissions to owner role', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $ownerRole = Role::where('name', 'Owner')->first();
    $allPermissions = Permission::count();
    
    expect($ownerRole->permissions->count())->toBe($allPermissions);
});

it('assigns limited permissions to employee role', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $employeeRole = Role::where('name', 'Employee')->first();
    $expectedPermissions = [
        'employees.view',
        'attendance.view',
        'attendance.mark',
        'leave.view',
        'leave.request',
        'reports.view',
    ];
    
    foreach ($expectedPermissions as $permissionName) {
        expect($employeeRole->hasPermissionTo($permissionName))->toBeTrue();
    }
    
    // Employee should not have admin permissions
    expect($employeeRole->hasPermissionTo('users.delete'))->toBeFalse();
    expect($employeeRole->hasPermissionTo('system.settings'))->toBeFalse();
});

// Role Assignment Service Tests

it('assigns role to user correctly', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $user = User::factory()->create();
    $service = new RoleAssignmentService();
    
    $service->assignRole($user, 'Manager');
    
    expect($user->hasRole('Manager'))->toBeTrue();
});

it('removes role from user correctly', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $user = User::factory()->create();
    $user->assignRole('Manager');
    
    $service = new RoleAssignmentService();
    $service->removeRole($user, 'Manager');
    
    expect($user->hasRole('Manager'))->toBeFalse();
});

it('syncs multiple roles correctly', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $user = User::factory()->create();
    $user->assignRole('Employee');
    
    $service = new RoleAssignmentService();
    $service->syncRoles($user, ['Manager', 'Employee Administrator']);
    
    expect($user->hasRole('Employee'))->toBeFalse();
    expect($user->hasRole('Manager'))->toBeTrue();
    expect($user->hasRole('Employee Administrator'))->toBeTrue();
});

it('gives and revokes permissions correctly', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $user = User::factory()->create();
    $service = new RoleAssignmentService();
    
    $service->givePermission($user, 'users.view');
    expect($user->hasPermissionTo('users.view'))->toBeTrue();
    
    $service->revokePermission($user, 'users.view');
    expect($user->hasPermissionTo('users.view'))->toBeFalse();
});

it('gets permissions grouped by category', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $service = new RoleAssignmentService();
    $permissionsByCategory = $service->getPermissionsByCategory();
    
    expect($permissionsByCategory)->toHaveKey('users');
    expect($permissionsByCategory)->toHaveKey('employees');
    expect($permissionsByCategory)->toHaveKey('payroll');
    expect($permissionsByCategory)->toHaveKey('attendance');
    expect($permissionsByCategory)->toHaveKey('leave');
    expect($permissionsByCategory)->toHaveKey('reports');
    expect($permissionsByCategory)->toHaveKey('system');
});

it('checks user permissions correctly', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $user = User::factory()->create();
    $user->assignRole('Employee');
    
    $service = new RoleAssignmentService();
    
    expect($service->userHasPermission($user, 'employees.view'))->toBeTrue();
    expect($service->userHasPermission($user, 'users.delete'))->toBeFalse();
});

it('gets users with specific role', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    User::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $manager1 = User::factory()->create();
    $manager2 = User::factory()->create();
    $employee = User::factory()->create();
    
    $manager1->assignRole('Manager');
    $manager2->assignRole('Manager');
    $employee->assignRole('Employee');
    
    $service = new RoleAssignmentService();
    $managers = $service->getUsersWithRole('Manager');
    
    expect($managers)->toHaveCount(2);
    expect($managers->pluck('id'))->toContain($manager1->id, $manager2->id);
    expect($managers->pluck('id'))->not->toContain($employee->id);
});

// Security Tests

it('prevents role assignment with invalid role name', function () {
    $user = User::factory()->create();
    $service = new RoleAssignmentService();
    
    expect(fn() => $service->assignRole($user, 'NonExistentRole'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('prevents permission assignment with invalid permission name', function () {
    $user = User::factory()->create();
    $service = new RoleAssignmentService();
    
    expect(fn() => $service->givePermission($user, 'nonexistent.permission'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('handles concurrent role assignments safely', function () {
    // Clean up any existing data first
    Role::query()->delete();
    Permission::query()->delete();
    
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    
    $user = User::factory()->create();
    $service = new RoleAssignmentService();
    
    // Simulate concurrent role assignments
    $service->assignRole($user, 'Employee');
    $service->assignRole($user, 'Manager');
    
    // User should have both roles
    expect($user->hasRole('Employee'))->toBeTrue();
    expect($user->hasRole('Manager'))->toBeTrue();
});
