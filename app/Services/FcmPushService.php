<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        if (! config('notifications.channels.push', false)) {
            return 0;
        }

        $serverKey = config('notifications.fcm.server_key');
        if (! $serverKey) {
            Log::info('FCM skipped: NOTIFICATION_FCM_SERVER_KEY missing');
            return 0;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values();

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->send($token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    public function send(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('notifications.fcm.server_key');
        if (! $serverKey) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key='.$serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => array_map('strval', $data),
                'priority' => 'high',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('FCM send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
