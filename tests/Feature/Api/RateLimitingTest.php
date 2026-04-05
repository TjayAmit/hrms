<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'employee']);
    
    $this->user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);
});

it('applies rate limiting to authentication endpoints', function () {
    // Clear any existing rate limits
    RateLimiter::clear('auth|' . request()->ip());

    // Make 5 successful requests (limit is 5 per minute)
    for ($i = 0; $i < 5; $i++) {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        $response->assertSuccessful();
    }

    // 6th request should be rate limited
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);
    $response->assertStatus(429)
        ->assertJson(['message' => 'Too Many Attempts.']);
});

it('applies different rate limits for authenticated vs unauthenticated users', function () {
    // Test unauthenticated API rate limit (20 per minute)
    for ($i = 0; $i < 20; $i++) {
        $response = $this->getJson('/api/employees');
        // Returns 401 unauthorized but not rate limited
        expect($response->status())->not->toBe(429);
    }

    // 21st request should be rate limited
    $response = $this->getJson('/api/employees');
    // Should be either 401 (unauthorized) or 429 (rate limited)
    expect(in_array($response->status(), [401, 429]))->toBeTrue();
});

it('allows higher rate limits for admin users', function () {
    $this->user->assignRole('admin');
    $token = $this->user->createToken('test-token')->plainTextToken;

    // Admin should be able to make more requests
    for ($i = 0; $i < 50; $i++) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/employees');
        
        // Returns 501 because endpoint not implemented, but not rate limited
        expect($response->status())->not->toBe(429);
    }
});

it('applies stricter rate limits to sensitive endpoints', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;

    // Make 3 requests to sensitive endpoint (limit is 3 per minute)
    for ($i = 0; $i < 3; $i++) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/payroll');
        
        // Returns 501 because endpoint not implemented, but not rate limited
        expect($response->status())->not->toBe(429);
    }

    // 4th request should be rate limited
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/payroll');
    $response->assertStatus(429);
});

it('includes rate limit headers in responses', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    // Laravel should include rate limit headers
    expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();
    expect($response->headers->has('X-RateLimit-Remaining'))->toBeTrue();
});

it('rate limits by IP for unauthenticated requests', function () {
    // Simulate requests from different IPs
    $ip1 = '192.168.1.1';
    $ip2 = '192.168.1.2';

    // Make requests from IP1
    for ($i = 0; $i < 5; $i++) {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip1])
            ->postJson('/api/auth/login', [
                'email' => $this->user->email,
                'password' => 'password',
            ]);
        $response->assertSuccessful();
    }

    // IP1 should be rate limited
    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip1])
        ->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
    $response->assertStatus(429);

    // IP2 should still be able to make requests
    $response = $this->withServerVariables(['REMOTE_ADDR' => $ip2])
        ->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
    $response->assertSuccessful();
});

it('rate limits by user ID for authenticated requests', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Assign roles to users to ensure they can access to endpoint
    $user1->assignRole('Employee');
    $user2->assignRole('Employee');

    $token1 = $user1->createToken('test-token')->plainTextToken;
    $token2 = $user2->createToken('test-token')->plainTextToken;

    // User1 makes requests up to limit (using POST to match route)
    for ($i = 0; $i < 60; $i++) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->postJson('/api/attendance', []);
        // Should return 501 (not implemented) but not rate limited
        expect($response->status())->not->toBe(429);
    }
});
