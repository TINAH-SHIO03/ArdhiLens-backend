<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class SellerKycDecisionService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function approve(User $user, ?string $adminNote = null): User
    {
        $note = trim(($user->kyc_notes ? $user->kyc_notes."\n" : '').($adminNote ?: 'Approved by admin '.now()->toDateTimeString()));

        $user->update([
            'kyc_status' => 'verified',
            'verified_at' => now(),
            'kyc_notes' => $note,
        ]);

        $user->refresh();
        $this->notifySeller($user, approved: true);

        return $user;
    }

    public function reject(User $user, string $reason): User
    {
        $user->update([
            'kyc_status' => 'rejected',
            'kyc_notes' => $reason,
            'verified_at' => null,
        ]);

        $user->refresh();
        $this->notifySeller($user, approved: false, reason: $reason);

        return $user;
    }

    private function notifySeller(User $user, bool $approved, ?string $reason = null): void
    {
        $title = $approved
            ? 'Seller KYC approved'
            : 'Seller KYC rejected';

        $body = $approved
            ? 'Your seller identity KYC was approved. You can continue ownership proof and receive buyer requests.'
            : 'Your seller KYC was rejected'.($reason ? ': '.$reason : '.').' Please resubmit with a valid NIN.';

        try {
            $this->notifications->createInAppNotification(
                $user,
                'kyc_decision',
                $title,
                $body,
                [
                    'kyc_status' => $user->kyc_status,
                    'screen' => 'seller_home',
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('Seller KYC decision saved but in-app notification failed', [
                'user_id' => $user->id,
                'kyc_status' => $user->kyc_status,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
