<?php

namespace App\Listeners;

use App\Events\RiskScoreAlert;
use App\Services\NotificationService;

class SendRiskAlertNotifications
{
    public function __construct(
        public readonly NotificationService $notificationService,
    ) {}

    public function handle(RiskScoreAlert $event): void
    {
        $this->notificationService->notifyRiskScoreAlert(
            $event->user,
            $event->verdict,
            $event->riskScore,
            $event->plot->plot_reference,
            $event->verificationLog->id,
        );
    }
}