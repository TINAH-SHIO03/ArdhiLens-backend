<?php

namespace App\Services;

use App\Mail\AuthCodeMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * @return array{otp: OtpCode, code: string, mail_sent: bool, mail_error: ?string}
     */
    public function issue(string $email, string $purpose, int $ttlMinutes = 15): array
    {
        $email = strtolower(trim($email));

        OtpCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        $otp = OtpCode::query()->create([
            'email' => $email,
            'code' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        [$mailSent, $mailError] = $this->deliver($email, $purpose, $code);

        if (app()->environment(['local', 'testing']) || config('app.debug')) {
            Log::info('OTP_CODE_DEV', [
                'email' => $email,
                'purpose' => $purpose,
                'code' => $code,
                'mail_sent' => $mailSent,
                'mail_error' => $mailError,
            ]);
        }

        return [
            'otp' => $otp,
            'code' => $code,
            'mail_sent' => $mailSent,
            'mail_error' => $mailError,
        ];
    }

    public function verify(string $email, string $purpose, string $code): bool
    {
        $otp = OtpCode::query()
            ->where('email', strtolower(trim($email)))
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired() || $otp->attempts >= 5) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    /**
     * @return array{0: bool, 1: ?string}
     */
    private function deliver(string $email, string $purpose, string $plainCode): array
    {
        try {
            Mail::mailer('smtp')->to($email)->send(new AuthCodeMail($email, $purpose, $plainCode));

            return [true, null];
        } catch (\Throwable $e) {
            Log::warning('OTP email failed', [
                'email' => $email,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            try {
                Mail::mailer('smtp')->raw(
                    "Your ArdhiLens code is {$plainCode}. It expires in 15 minutes.",
                    function ($message) use ($email, $purpose) {
                        $message->to($email)->subject(
                            $purpose === 'password_reset'
                                ? 'ArdhiLens password reset code'
                                : 'ArdhiLens email verification code'
                        );
                    }
                );

                return [true, null];
            } catch (\Throwable $inner) {
                Log::error('OTP fallback email failed', [
                    'email' => $email,
                    'error' => $inner->getMessage(),
                ]);

                return [false, $inner->getMessage()];
            }
        }
    }

    public function resetPassword(string $email, string $newPassword): ?User
    {
        $user = User::query()->where('email', strtolower(trim($email)))->first();
        if (! $user) {
            return null;
        }

        $user->update([
            'password' => $newPassword,
            'remember_token' => Str::random(60),
        ]);

        return $user;
    }
}
