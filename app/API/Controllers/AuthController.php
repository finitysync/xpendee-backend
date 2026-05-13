<?php

namespace App\API\Controllers;

use App\API\Requests\LoginRequest;
use App\API\Requests\RegisterRequest;
use App\API\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function register(RegisterRequest $request)
    {
        $tenant = Tenant::create(array_merge(
            $request->validated(),
            [
                'trial_ends_at' => now()->addDays(7),
                'status' => 'trial',
            ]
        ));

        $token = $tenant->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'tenant' => new TenantResource($tenant),
            'token' => $token,
        ], 'Registration successful', 201);
    }

    public function login(LoginRequest $request)
    {
        $tenant = Tenant::where('email', $request->email)->first();

        if (!$tenant || !Hash::check($request->password, $tenant->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        if ($tenant->status === 'suspended') {
            return $this->errorResponse('Account suspended', 403);
        }

        $tenant->tokens()->delete();
        $token = $tenant->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'tenant' => new TenantResource($tenant),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Logged out');
    }

    public function me(Request $request)
    {
        return $this->successResponse(new TenantResource($request->user()));
    }
}
