<?php

namespace App\Listeners;

use App\Events\VerificationCompleted;
use App\Models\User;
use App\Services\NotificationService;

class SendVerificationNotifications
{
    public function __construct(
        public readonly NotificationService $notificationService,
    ) {}

    public function handle(VerificationCompleted $event): void
    {
        $this->notificationService->notifyVerificationResult(
            $event->user,
            $event->verificationLog->ai_verdict ?? 'INCOMPLETE',
            $event->verificationLog->risk_score ?? 0,
            $event->plot->plot_reference,
            $event->verificationLog->id,
        );

        // Notify linked seller/owner when a buyer completes verification.
        $ownerNin = $event->plot->owner_nida;
        if (! $ownerNin) {
            return;
        }

        $seller = User::query()
            ->where('nin', $ownerNin)
            ->where('role', 'seller')
            ->where('id', '!=', $event->user->id)
            ->first();

        if ($seller) {
            $this->notificationService->notifySellerOfBuyerVerification(
                $seller,
                $event->verificationLog->ai_verdict ?? 'INCOMPLETE',
                $event->verificationLog->risk_score ?? 0,
                $event->plot->plot_reference,
                $event->verificationLog->id,
                $event->user->name,
            );
        }
    }
}
