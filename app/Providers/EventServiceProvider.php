<?php

namespace App\Providers;

use App\Events\PlotStatusChanged;
use App\Events\RiskScoreAlert;
use App\Events\VerificationCompleted;
use App\Listeners\SendPlotStatusNotifications;
use App\Listeners\SendRiskAlertNotifications;
use App\Listeners\SendVerificationNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        VerificationCompleted::class => [
            SendVerificationNotifications::class,
        ],
        PlotStatusChanged::class => [
            SendPlotStatusNotifications::class,
        ],
        RiskScoreAlert::class => [
            SendRiskAlertNotifications::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}