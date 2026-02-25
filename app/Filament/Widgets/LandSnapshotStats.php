<?php

namespace App\Filament\Widgets;

use App\Models\Plot;
use App\Models\PlotDispute;
use App\Models\VerificationLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LandSnapshotStats extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Land Operations Snapshot';

    protected ?string $description = 'Live metrics for plot registry, disputes, and verification outcomes.';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $totalPlots = Plot::query()->count();
        $activePlots = Plot::query()->where('status', 'Active')->count();
        $openDisputes = PlotDispute::query()->where('status', 'Ongoing')->count();
        $doubleAllocations = Plot::query()->where('double_allocation_flag', true)->count();
        $completedVerifications = VerificationLog::query()->where('status', 'Completed')->count();
        $highRiskVerifications = VerificationLog::query()
            ->whereIn('ai_verdict', ['CAUTION', 'DO_NOT_BUY'])
            ->count();

        return [
            Stat::make('Total Plots', number_format($totalPlots))
                ->description('All registered land parcels')
                ->color('primary'),
            Stat::make('Active Plots', number_format($activePlots))
                ->description('Ready for regular operations')
                ->color('success'),
            Stat::make('Open Disputes', number_format($openDisputes))
                ->description('Ongoing legal or ownership disputes')
                ->color('warning'),
            Stat::make('Double Allocation Flags', number_format($doubleAllocations))
                ->description('Plots requiring urgent investigation')
                ->color('danger'),
            Stat::make('Completed Verifications', number_format($completedVerifications))
                ->description('Verification logs with final status')
                ->color('info'),
            Stat::make('High-Risk Verifications', number_format($highRiskVerifications))
                ->description('AI verdict: CAUTION or DO_NOT_BUY')
                ->color('danger'),
        ];
    }
}
