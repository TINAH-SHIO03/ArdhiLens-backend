<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExpiringCertificatesWidget;
use App\Filament\Widgets\LandSnapshotStats;
use App\Filament\Widgets\PendingDocumentsWidget;
use App\Filament\Widgets\PendingSellerKycWidget;
use App\Filament\Widgets\RecentCertificatesWidget;
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
            PendingSellerKycWidget::class,
            PendingDocumentsWidget::class,
            RecentCertificatesWidget::class,
            ExpiringCertificatesWidget::class,
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
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
