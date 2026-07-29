<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(?string $phoneNumber, string $message): bool
    {
        if (! config('notifications.channels.sms', false) || empty($phoneNumber)) {
            return false;
        }

        $provider = config('notifications.sms.provider', 'log');

        return match ($provider) {
            'twilio' => $this->sendTwilio($phoneNumber, $message),
            default => $this->sendLog($phoneNumber, $message),
        };
    }

    private function sendTwilio(string $phone, string $message): bool
    {
        $sid = config('notifications.sms.twilio_sid');
        $token = config('notifications.sms.twilio_token');
        $from = config('notifications.sms.twilio_from');

        if (! $sid || ! $token || ! $from) {
            return $this->sendLog($phone, $message);
        }

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $phone,
                    'Body' => $message,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('Twilio SMS failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function sendLog(string $phone, string $message): bool
    {
        Log::info('SMS_OUTBOX', ['to' => $phone, 'message' => $message]);

        return true;
    }
}
