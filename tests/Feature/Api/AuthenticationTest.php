<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
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

it('can issue API token on login', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'roles',
            ],
            'token',
            'token_type',
        ]);

    expect($response->json('token_type'))->toBe('Bearer');
    expect($response->json('user.email'))->toBe($this->user->email);
});

it('cannot login with invalid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('can register new user and receive token', function () {
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'roles',
            ],
            'token',
            'token_type',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

it('validates registration data', function (array $userData, array $errors) {
    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'missing name' => [
        ['email' => 'test@example.com', 'password' => 'password'],
        ['name'],
    ],
    'invalid email' => [
        ['name' => 'John', 'email' => 'invalid-email', 'password' => 'password'],
        ['email'],
    ],
    'password mismatch' => [
        [
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ],
        ['password'],
    ],
]);

it('can access protected routes with valid token', function () {
    $token = $this->user->createToken('test-token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/auth/user');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'roles',
            ],
        ]);
});

it('cannot access protected routes without token', function () {
    $response = $this->getJson('/api/auth/user');

    $response->assertUnauthorized();
});

it('cannot access protected routes with invalid token', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer invalid-token',
    ])->getJson('/api/auth/user');

    $response->assertUnauthorized();
});

it('can logout and revoke current token', function () {
    $token = $this->user->createToken('test-token');

    // Verify token exists before logout
    expect($this->user->tokens()->count())->toBe(1);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token->plainTextToken,
    ])->postJson('/api/auth/logout');

    $response->assertSuccessful()
        ->assertJson(['message' => 'Successfully logged out']);

    // Verify token is revoked from database
    expect($this->user->tokens()->count())->toBe(0);
});

it('can refresh API token', function () {
    $oldToken = $this->user->createToken('test-token');

    // Verify old token exists
    expect($this->user->tokens()->count())->toBe(1);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $oldToken->plainTextToken,
    ])->postJson('/api/auth/refresh');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'user',
            'token',
            'token_type',
        ]);

    $newToken = $response->json('token');
    expect($newToken)->not->toBe($oldToken->plainTextToken);

    // Verify only one token exists (old revoked, new created)
    expect($this->user->tokens()->count())->toBe(1);
    
    // Test that new token works
    $this->withHeaders([
        'Authorization' => 'Bearer ' . $newToken,
    ])->getJson('/api/auth/user')->assertSuccessful();
});

it('can list all user tokens', function () {
    $this->user->createToken('token1');
    $this->user->createToken('token2');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->user->createToken('current-token')->plainTextToken,
    ])->getJson('/api/auth/tokens');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'tokens' => [
                '*' => [
                    'id',
                    'name',
                    'created_at',
                    'last_used_at',
                ],
            ],
        ]);

    expect($response->json('tokens'))->toHaveCount(3);
});

it('can revoke specific token', function () {
    $tokenToRevoke = $this->user->createToken('token-to-revoke');
    $currentToken = $this->user->createToken('current-token');

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $currentToken->plainTextToken,
    ])->deleteJson("/api/auth/tokens/{$tokenToRevoke->accessToken->id}");

    $response->assertSuccessful()
        ->assertJson(['message' => 'Token revoked successfully']);

    expect($this->user->tokens()->find($tokenToRevoke->accessToken->id))->toBeNull();
});

it('maintains single session by revoking existing tokens on login', function () {
    // Create existing tokens
    $this->user->createToken('existing-token1');
    $this->user->createToken('existing-token2');

    expect($this->user->tokens()->count())->toBe(2);

    // Login should revoke existing tokens and create new one
    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful();

    // Should only have the new token
    expect($this->user->tokens()->count())->toBe(1);
    expect($this->user->tokens()->first()->name)->toBe('api-token');
});

it('includes user roles in authentication responses', function () {
    $this->user->assignRole('admin');

    $response = $this->postJson('/api/auth/login', [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('user.roles', function ($roles) {
            return collect($roles)->pluck('name')->contains('admin');
        });
});
