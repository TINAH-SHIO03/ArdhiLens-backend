<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LandSnapshotStats;
use App\Filament\Widgets\RecentHighRiskVerificationsWidget;
use App\Filament\Widgets\RecentOwnershipTransfersWidget;
use App\Filament\Widgets\VerificationVerdictChartWidget;
use Filament\Pages\Dashboard;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class LandDashboard extends Dashboard
{
    protected static ?string $title = 'ArdhiLens Command Center';

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            LandSnapshotStats::class,
            VerificationVerdictChartWidget::class,
            RecentHighRiskVerificationsWidget::class,
            RecentOwnershipTransfersWidget::class,
        ];
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
