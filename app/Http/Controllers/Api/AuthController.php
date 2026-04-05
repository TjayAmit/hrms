<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login and create API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = $request->user();
        
        // Revoke existing tokens to maintain single session
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Handle user registration and create API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign default role
        $user->assignRole('employee');

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Handle user logout and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Refresh the current API token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('roles'),
        ]);
    }

    /**
     * Get all tokens for the authenticated user.
     */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->get(['id', 'name', 'created_at', 'last_used_at']);

        return response()->json([
            'tokens' => $tokens,
        ]);
    }

    /**
     * Revoke a specific token.
     */
    public function revokeToken(Request $request, string $token): JsonResponse
    {
        $user = $request->user();
        
        $accessToken = $user->tokens()->findOrFail($token);
        $accessToken->delete();

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }
}
