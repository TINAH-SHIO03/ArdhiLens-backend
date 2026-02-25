<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:15'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.auth.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone_number' => $validated['phone_number'] ?? null,
            'role' => 'buyer',
            'is_active' => true,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success(__('api.auth.registration_successful'), [
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.auth.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error(__('api.auth.invalid_credentials'), [], 401);
        }

        if (! $user->is_active) {
            return $this->error(__('api.auth.inactive_account'), [], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success(__('api.auth.login_successful'), [
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success(__('api.auth.logout_successful'));
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.auth.unauthenticated'), [], 401);
        }

        return $this->success(__('api.auth.authenticated_user'), [
            'user' => $this->userPayload($user),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'nin' => $user->nin,
            'role' => $user->role,
            'phone_number' => $user->phone_number,
            'is_active' => (bool) $user->is_active,
            'verified_at' => $user->verified_at?->toIso8601String(),
        ];
    }

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => (object) [],
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }

    private function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => (object) [],
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
