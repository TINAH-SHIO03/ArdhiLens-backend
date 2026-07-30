<?php

namespace App\Filament\Widgets;

use App\Models\Plot;
use App\Models\PlotDispute;
use App\Models\User;
use App\Models\VerificationCertificate;
use App\Models\VerificationLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class LandSnapshotStats extends StatsOverviewWidget
{
    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Land Operations Snapshot';

    protected ?string $description = 'Live metrics for plot registry, disputes, and verification outcomes.';

    protected ?string $pollingInterval = null;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $metrics = Cache::remember('filament.land_snapshot_stats', 45, function (): array {
            $plots = Plot::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active")
                ->selectRaw('SUM(CASE WHEN double_allocation_flag = 1 THEN 1 ELSE 0 END) as double_alloc')
                ->first();

            $verifications = VerificationLog::query()
                ->selectRaw("SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed")
                ->selectRaw("SUM(CASE WHEN ai_verdict IN ('CAUTION', 'DO_NOT_BUY') THEN 1 ELSE 0 END) as high_risk")
                ->first();

            return [
                'total_plots' => (int) ($plots->total ?? 0),
                'active_plots' => (int) ($plots->active ?? 0),
                'double_alloc' => (int) ($plots->double_alloc ?? 0),
                'open_disputes' => (int) PlotDispute::query()->where('status', 'Ongoing')->count(),
                'completed' => (int) ($verifications->completed ?? 0),
                'high_risk' => (int) ($verifications->high_risk ?? 0),
                'kyc_pending' => (int) User::query()
                    ->where('role', 'seller')
                    ->whereIn('kyc_status', ['pending_review', 'needs_manual_review', 'required'])
                    ->count(),
                'certificates_issued' => (int) VerificationCertificate::query()->count(),
                'certificates_expiring' => (int) VerificationCertificate::query()
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays(30)])
                    ->count(),
            ];
        });

        return [
            Stat::make('Total Plots', number_format($metrics['total_plots']))
                ->description('All registered land parcels')
                ->color('primary'),
            Stat::make('Active Plots', number_format($metrics['active_plots']))
                ->description('Ready for regular operations')
                ->color('success'),
            Stat::make('Open Disputes', number_format($metrics['open_disputes']))
                ->description('Ongoing legal or ownership disputes')
                ->color('warning'),
            Stat::make('Double Allocation Flags', number_format($metrics['double_alloc']))
                ->description('Plots requiring urgent investigation')
                ->color('danger'),
            Stat::make('Completed Verifications', number_format($metrics['completed']))
                ->description('Verification logs with final status')
                ->color('info'),
            Stat::make('High-Risk Verifications', number_format($metrics['high_risk']))
                ->description('AI verdict: CAUTION or DO_NOT_BUY')
                ->color('danger'),
            Stat::make('Seller KYC pending', number_format($metrics['kyc_pending']))
                ->description('Awaiting admin review')
                ->color('warning'),
            Stat::make('Certificates issued', number_format($metrics['certificates_issued']))
                ->description('Pre-purchase and ownership PDFs')
                ->color('success'),
            Stat::make('Certs expiring soon', number_format($metrics['certificates_expiring']))
                ->description('Within the next 30 days')
                ->color('warning'),
        ];
    }

    public static function flushCache(): void
    {
        Cache::forget('filament.land_snapshot_stats');
    }
}
