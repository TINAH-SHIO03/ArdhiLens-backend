<?php

namespace App\Services;

use App\Mail\ProcedureAlertMail;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Create an in-app notification and optionally email the same content.
     */
    public function createInAppNotification(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): UserNotification {
        $notification = $user->notifications()->create([
            'type'  => $type,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
        ]);

        if (config('notifications.channels.email', true)) {
            $this->sendProcedureEmail($user, $title, $body, $type, $data);
        }

        if (config('notifications.channels.push', false)) {
            try {
                app(FcmPushService::class)->sendToUser($user, $title, $body, [
                    'type' => $type,
                    ...collect($data)->map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v))->all(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Push notification failed', ['error' => $e->getMessage()]);
            }
        }

        if (config('notifications.channels.sms', false)) {
            try {
                app(SmsService::class)->send($user->phone_number, $title.': '.$body);
            } catch (\Throwable $e) {
                Log::warning('SMS notification failed', ['error' => $e->getMessage()]);
            }
        }

        return $notification;
    }

    public function markAsRead(int $notificationId, User $user): bool
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    public function markAllAsRead(User $user): int
    {
        return $user->notifications()
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotificationsCount();
    }

    public function getNotifications(User $user, int $page = 1, int $perPage = 20): array
    {
        $query = $user->notifications()->orderByDesc('created_at');

        $total = $query->count();
        $notifications = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'notifications' => $notifications,
            'total'         => $total,
            'page'          => $page,
            'per_page'      => $perPage,
            'total_pages'   => (int) ceil($total / $perPage),
        ];
    }

    public function deleteNotification(int $notificationId, User $user): bool
    {
        $notification = $user->notifications()->where('id', $notificationId)->first();

        if (! $notification) {
            return false;
        }

        $notification->delete();
        return true;
    }

    public function notifyVerificationResult(
        User $user,
        string $verdict,
        int $riskScore,
        string $plotReference,
        int $verificationLogId
    ): UserNotification {
        $verdictLabel = match ($verdict) {
            'SAFE' => (string) __('api.notifications.verdict_safe'),
            'CAUTION' => (string) __('api.notifications.verdict_caution'),
            'DO_NOT_BUY' => (string) __('api.notifications.verdict_do_not_buy'),
            default => $verdict,
        };

        $role = strtolower((string) $user->role);
        $title = (string) __('api.notifications.verification_complete_title', ['plot' => $plotReference]);
        $bodyKey = $role === 'seller'
            ? 'api.notifications.verification_complete_body_seller'
            : 'api.notifications.verification_complete_body_buyer';
        $body = (string) __($bodyKey, [
            'plot' => $plotReference,
            'verdict' => $verdictLabel,
            'score' => $riskScore,
        ]);

        return $this->createInAppNotification(
            $user,
            'verification_result',
            $title,
            $body,
            [
                'verdict'             => $verdict,
                'verdict_label'       => $verdictLabel,
                'risk_score'          => $riskScore,
                'plot_reference'      => $plotReference,
                'verification_log_id' => $verificationLogId,
                'role'                => $role,
            ]
        );
    }

    public function notifySellerOfBuyerVerification(
        User $seller,
        string $verdict,
        int $riskScore,
        string $plotReference,
        int $verificationLogId,
        string $buyerName
    ): UserNotification {
        $title = (string) __('api.notifications.seller_buyer_verified_title', ['plot' => $plotReference]);
        $body = (string) __('api.notifications.seller_buyer_verified_body', [
            'buyer' => $buyerName,
            'plot' => $plotReference,
            'verdict' => $verdict,
            'score' => $riskScore,
        ]);

        return $this->createInAppNotification(
            $seller,
            'verification_result',
            $title,
            $body,
            [
                'verdict'             => $verdict,
                'risk_score'          => $riskScore,
                'plot_reference'      => $plotReference,
                'verification_log_id' => $verificationLogId,
                'buyer_name'          => $buyerName,
                'role'                => 'seller',
                'event'               => 'seller_plot_verified',
            ]
        );
    }

    public function notifyPlotStatusChange(
        User $user,
        string $plotReference,
        string $oldStatus,
        string $newStatus
    ): UserNotification {
        $title = (string) __('api.notifications.plot_status_title', ['plot' => $plotReference]);
        $body = (string) __('api.notifications.plot_status_body', [
            'plot' => $plotReference,
            'old' => $oldStatus,
            'new' => $newStatus,
        ]);

        return $this->createInAppNotification(
            $user,
            'plot_status_change',
            $title,
            $body,
            [
                'plot_reference' => $plotReference,
                'old_status'     => $oldStatus,
                'new_status'     => $newStatus,
                'role'           => strtolower((string) $user->role),
            ]
        );
    }

    public function notifyRiskScoreAlert(
        User $user,
        string $verdict,
        int $riskScore,
        string $plotReference,
        int $verificationLogId
    ): UserNotification {
        $title = (string) __('api.notifications.risk_alert_title', ['plot' => $plotReference]);
        $body = (string) __('api.notifications.risk_alert_body', [
            'plot' => $plotReference,
            'score' => $riskScore,
            'verdict' => $verdict,
        ]);

        return $this->createInAppNotification(
            $user,
            'risk_score_alert',
            $title,
            $body,
            [
                'verdict'             => $verdict,
                'risk_score'          => $riskScore,
                'plot_reference'      => $plotReference,
                'verification_log_id' => $verificationLogId,
                'priority'            => 'high',
                'role'                => strtolower((string) $user->role),
            ]
        );
    }

    public function notifySystem(
        User $user,
        string $title,
        string $body,
        array $data = []
    ): UserNotification {
        return $this->createInAppNotification($user, 'system', $title, $body, $data);
    }

    private function sendProcedureEmail(
        User $user,
        string $title,
        string $body,
        string $type,
        array $data
    ): void {
        if (empty($user->email)) {
            return;
        }

        try {
            Mail::mailer('smtp')->to($user->email)->send(new ProcedureAlertMail(
                $user,
                $title,
                $body,
                $this->procedureGuideFor($user, $type, $data),
                $data,
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send ArdhiLens procedure email', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function procedureGuideFor(User $user, string $type, array $data): string
    {
        $role = strtolower((string) ($data['role'] ?? $user->role ?? 'buyer'));

        if ($role === 'seller') {
            return match ($type) {
                'verification_result', 'seller_plot_verified' =>
                    (string) __('api.notifications.procedure_seller_verification'),
                'risk_score_alert' =>
                    (string) __('api.notifications.procedure_seller_risk'),
                'plot_status_change' =>
                    (string) __('api.notifications.procedure_seller_status'),
                'kyc_decision' =>
                    (string) __('api.notifications.procedure_seller_default'),
                default =>
                    (string) __('api.notifications.procedure_seller_default'),
            };
        }

        return match ($type) {
            'verification_result' =>
                (string) __('api.notifications.procedure_buyer_verification'),
            'risk_score_alert' =>
                (string) __('api.notifications.procedure_buyer_risk'),
            'plot_status_change' =>
                (string) __('api.notifications.procedure_buyer_status'),
            default =>
                (string) __('api.notifications.procedure_buyer_default'),
        };
    }
}
