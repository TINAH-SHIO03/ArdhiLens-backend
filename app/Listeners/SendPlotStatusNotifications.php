<?php

namespace App\Listeners;

use App\Events\PlotStatusChanged;
use App\Models\Nida;
use App\Models\User;
use App\Services\NotificationService;

class SendPlotStatusNotifications
{
    public function __construct(
        public readonly NotificationService $notificationService,
    ) {}

    public function handle(PlotStatusChanged $event): void
    {
        $plot = $event->plot;
        $ownerNida = Nida::where('nin', $plot->owner_nida)->first();

        if ($ownerNida) {
            $ownerUser = User::where('nin', $ownerNida->nin)->first();

            if ($ownerUser) {
                $this->notificationService->notifyPlotStatusChange(
                    $ownerUser,
                    $plot->plot_reference,
                    $event->oldStatus,
                    $event->newStatus,
                );
            }
        }
    }
}