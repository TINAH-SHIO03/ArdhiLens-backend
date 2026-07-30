<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\OtpService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $this->ensureNotRateLimited($request, 'auth-register', 8);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'role' => ['nullable', 'string', 'in:buyer,seller'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.auth.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
        $validated['email'] = strtolower(trim($validated['email']));

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone_number' => $validated['phone_number'] ?? null,
            'role' => $validated['role'] ?? 'buyer',
            'is_active' => true,
            'kyc_status' => ($validated['role'] ?? 'buyer') === 'seller' ? 'required' : 'none',
        ]);

        try {
            $issued = $this->otpService->issue($user->email, 'email_verify');
            if (! $issued['mail_sent']) {
                Log::warning('Email verification OTP mail not delivered', [
                    'user_id' => $user->id,
                    'error' => $issued['mail_error'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Email verification OTP failed after register', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            Mail::mailer('smtp')->to($user->email)->send(new WelcomeUserMail($user));
        } catch (\Throwable $e) {
            Log::warning('Welcome email failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->auditLogService->log('auth.register', $user, 'user', $user->id, [
            'role' => $user->role,
        ], $request);

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success(__('api.auth.registration_successful'), [
            'token' => $token,
            'user' => $this->userPayload($user),
            'email_verification_required' => true,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $this->ensureNotRateLimited($request, 'auth-login', 10);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:1'],
        ]);

        if ($validator->fails()) {
            $first = collect($validator->errors()->all())->first() ?? __('api.auth.validation_failed');

            return $this->error($first, [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
        $email = strtolower(trim($validated['email']));

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($this->throttleKey($request, 'auth-login'));

            return $this->error(__('api.auth.invalid_credentials'), [], 401);
        }

        if (! $user->is_active) {
            return $this->error(__('api.auth.inactive_account'), [], 403);
        }

        // Admin accounts are web-only (Filament at /admin). Block mobile/API login.
        if ($user->isAdmin()) {
            return $this->error(__('api.auth.admin_web_only'), [], 403);
        }

        RateLimiter::clear($this->throttleKey($request, 'auth-login'));
        $token = $user->createToken('mobile')->plainTextToken;
        try {
            $this->auditLogService->log('auth.login', $user, 'user', $user->id, [], $request);
        } catch (\Throwable $e) {
            Log::warning('Audit log failed on login', ['error' => $e->getMessage()]);
        }

        return $this->success(__('api.auth.login_successful'), [
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()?->currentAccessToken()?->delete();
        if ($user) {
            $this->auditLogService->log('auth.logout', $user, 'user', $user->id, [], $request);
        }

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

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.auth.unauthenticated'), [], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:15'],
            'nin' => ['nullable', 'string', 'size:20', 'unique:users,nin,'.$user->id],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.auth.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
            if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $validated['email_verified_at'] = null;
            $this->otpService->issue($validated['email'], 'email_verify');
        }

        $user->update($validated);
        $this->auditLogService->log('auth.profile_update', $user, 'user', $user->id, $validated, $request);

        return $this->success(__('api.auth.profile_updated'), [
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error(__('api.auth.unauthenticated'), [], 401);
        }

        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp,heic,heif'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.auth.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $file = $request->file('avatar');
        if ($file === null) {
            return $this->error('No image file received.', [], 422);
        }

        if ($user->avatar_path && Storage::disk('local')->exists($user->avatar_path)) {
            Storage::disk('local')->delete($user->avatar_path);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension === '' || $extension === 'jpeg') {
            $extension = 'jpg';
        }

        $path = $file->storeAs(
            'avatars',
            $user->id.'_'.time().'.'.$extension,
            'local'
        );

        $user->update(['avatar_path' => $path]);
        $this->auditLogService->log('auth.avatar_upload', $user, 'user', $user->id, [], $request);

        return $this->success('Profile photo updated.', [
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function avatar(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error(__('api.auth.unauthenticated'), [], 401);
        }

        if (! $user->avatar_path || ! Storage::disk('local')->exists($user->avatar_path)) {
            return $this->error('No profile photo.', [], 404);
        }

        return Storage::disk('local')->response($user->avatar_path);
    }

    public function sendEmailVerification(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error(__('api.auth.unauthenticated'), [], 401);
        }

        $this->ensureNotRateLimited($request, 'auth-otp', 5);
        $issued = $this->otpService->issue($user->email, 'email_verify');

        $payload = [];
        if (! $issued['mail_sent'] && (config('app.debug') || app()->environment('local'))) {
            $payload['debug_code'] = $issued['code'];
            $payload['mail_sent'] = false;
        }

        if (! $issued['mail_sent'] && ! (config('app.debug') || app()->environment('local'))) {
            return $this->error('Could not send verification email. Check mail settings.', [
                'mail' => $issued['mail_error'],
            ], 500);
        }

        return $this->success(
            $issued['mail_sent']
                ? 'Verification code sent to your email.'
                : 'Email delivery failed. Use the on-screen code (dev mode).',
            $payload
        );
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return $this->error(__('api.auth.unauthenticated'), [], 401);
        }

        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.auth.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        if (! $this->otpService->verify($user->email, 'email_verify', $request->input('code'))) {
            return $this->error('Invalid or expired verification code.', [], 422);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'verified_at' => $user->verified_at ?? now(),
        ])->save();

        $this->auditLogService->log('auth.email_verified', $user, 'user', $user->id, [], $request);

        return $this->success('Email verified successfully.', [
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $this->ensureNotRateLimited($request, 'auth-forgot', 5);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.auth.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));
        $user = User::query()->where('email', $email)->first();

        // Always respond with the same shape so accounts cannot be enumerated,
        // but when mail fails in local/debug, return the code so recovery works.
        $payload = [
            'mail_sent' => false,
        ];

        if ($user) {
            try {
                $issued = $this->otpService->issue($email, 'password_reset');
                $this->auditLogService->log('auth.password_reset_requested', $user, 'user', $user->id, [
                    'mail_sent' => $issued['mail_sent'],
                ], $request);

                $payload['mail_sent'] = $issued['mail_sent'];

                if (! $issued['mail_sent']) {
                    Log::error('Password reset OTP mail not delivered', [
                        'email' => $email,
                        'error' => $issued['mail_error'],
                    ]);

                    if (config('app.debug') || app()->environment('local')) {
                        $payload['debug_code'] = $issued['code'];
                        $payload['dev_hint'] = 'SMTP failed. Use debug_code in the app.';
                    } else {
                        return $this->error('Could not send reset code. Please try again.', [
                            'mail' => $issued['mail_error'],
                        ], 500);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Password reset OTP failed', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);

                return $this->error('Could not send reset code. Please try again.', [
                    'mail' => $e->getMessage(),
                ], 500);
            }
        }

        $message = ($payload['mail_sent'] ?? false)
            ? 'Reset code sent. Check inbox and spam.'
            : ((isset($payload['debug_code']))
                ? 'Email could not be delivered. Use the code shown in the app.'
                : 'If the account exists, a reset code was sent.');

        return $this->success($message, $payload);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $this->ensureNotRateLimited($request, 'auth-reset', 5);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.auth.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));
        if (! $this->otpService->verify($email, 'password_reset', $request->input('code'))) {
            return $this->error('Invalid or expired reset code.', [], 422);
        }

        $user = $this->otpService->resetPassword($email, $request->input('password'));
        if (! $user) {
            return $this->error('Account not found.', [], 404);
        }

        $user->tokens()->delete();
        $this->auditLogService->log('auth.password_reset', $user, 'user', $user->id, [], $request);

        return $this->success('Password updated. Please sign in again.');
    }

    private function ensureNotRateLimited(Request $request, string $action, int $max): void
    {
        $key = $this->throttleKey($request, $action);
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Too many attempts. Try again later.',
                'data' => (object) [],
                'errors' => ['throttle' => true],
                'timestamp' => now()->toIso8601String(),
            ], 429));
        }

        RateLimiter::hit($key, 60);
    }

    private function throttleKey(Request $request, string $action): string
    {
        return $action.'|'.$request->ip().'|'.strtolower((string) $request->input('email', $request->user()?->email));
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
            'has_avatar' => filled($user->avatar_path),
            'is_active' => (bool) $user->is_active,
            'email_verified' => $user->email_verified_at !== null,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'verified_at' => $user->verified_at?->toIso8601String(),
            'kyc_status' => $user->kyc_status ?? 'none',
            'kyc_submitted_at' => $user->kyc_submitted_at?->toIso8601String(),
            'face_match_score' => $user->face_match_score,
            'face_match_passed' => $user->face_match_passed,
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
            'errors' => empty($errors) ? (object) [] : $errors,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
