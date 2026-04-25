<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\LoginDTO;
use App\DTOs\RegisterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(RegisterDTO::fromArray($request->validated()));

        return response()->json([
            'message' => 'Registration successful.',
            'data' => new UserResource($result['user']),
            'token' => $result['token'],
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(LoginDTO::fromArray($request->validated()));

        // Eager-load roles + permissions so the UserResource returns a
        // populated permissions array on first response — avoids N+1
        // queries inside the resource and lights up AuthContext.can() on
        // the frontend immediately after login.
        $result['user']->load(['company', 'roles', 'permissions']);

        return response()->json([
            'message' => 'Login successful.',
            'data' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->load(['company', 'roles', 'permissions'])
        );
    }
}
