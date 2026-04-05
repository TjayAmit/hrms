<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

it('allows cross-origin requests to API endpoints', function () {
    $response = $this->withHeaders([
        'Origin' => 'https://example.com',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'Content-Type, Authorization',
    ])->options('/api/auth/login');

    $response->assertSuccessful();
    // Check that CORS headers are present
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();
    expect($response->headers->has('Access-Control-Allow-Methods'))->toBeTrue();
    expect($response->headers->has('Access-Control-Allow-Headers'))->toBeTrue();
});

it('includes CORS headers in actual API responses', function () {
    $response = $this->withHeaders([
        'Origin' => 'https://mobile-app.com',
    ])->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful();
    // Check that CORS headers are present (may reflect origin or use wildcard)
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();
});

it('supports credentials in CORS requests', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeaders([
        'Origin' => 'https://trusted-app.com',
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/auth/user');

    $response->assertSuccessful();
    // The actual header value depends on configuration, but should be present
    expect($response->headers->has('Access-Control-Allow-Credentials'))->toBeTrue();
});

it('handles preflight requests correctly', function () {
    $response = $this->withHeaders([
        'Origin' => 'https://spa-app.com',
        'Access-Control-Request-Method' => 'DELETE',
        'Access-Control-Request-Headers' => 'Authorization, Content-Type',
    ])->options('/api/auth/tokens/1');

    $response->assertSuccessful();
    // Check that CORS headers are present
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();
    expect($response->headers->has('Access-Control-Allow-Methods'))->toBeTrue();
    expect($response->headers->has('Access-Control-Allow-Headers'))->toBeTrue();
});

it('allows different origins for API access', function () {
    $origins = [
        'https://mobile-app.example.com',
        'https://admin-panel.example.com',
        'http://localhost:3000',
        'http://127.0.0.1:8080',
    ];

    foreach ($origins as $origin) {
        $response = $this->withHeaders([
            'Origin' => $origin,
        ])->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertSuccessful();
        // Check that CORS headers are present
        expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();
    }
});

it('includes necessary headers for mobile clients', function () {
    $response = $this->withHeaders([
        'Origin' => 'https://mobile-app.com',
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
    ])->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful();
    // Check that CORS headers are present (value may vary based on config)
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();
});

it('handles complex CORS scenarios', function () {
    // Test with multiple custom headers
    $response = $this->withHeaders([
        'Origin' => 'https://advanced-client.com',
        'Access-Control-Request-Method' => 'PATCH',
        'Access-Control-Request-Headers' => 'Authorization, Content-Type, X-Custom-Header, X-Client-Version',
    ])->options('/api/employees/1');

    $response->assertSuccessful();
    // Check that CORS headers are present
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();
    expect($response->headers->has('Access-Control-Allow-Methods'))->toBeTrue();
    expect($response->headers->has('Access-Control-Allow-Headers'))->toBeTrue();
});

it('maintains CORS configuration across different HTTP methods', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;
    $headers = [
        'Origin' => 'https://multi-method-client.com',
        'Authorization' => 'Bearer ' . $token,
    ];

    // Test GET
    $response = $this->withHeaders($headers)->getJson('/api/auth/user');
    $response->assertSuccessful();
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();

    // Test POST
    $response = $this->withHeaders($headers)->postJson('/api/auth/refresh');
    $response->assertSuccessful();
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();

    // Test DELETE - logout endpoint should work
    $response = $this->withHeaders($headers)->postJson('/api/auth/logout');
    $response->assertSuccessful();
    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeTrue();
});
