<?php

namespace App\Filament\Widgets;

use App\Models\VerificationLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VerificationVerdictChartWidget extends ChartWidget
{
    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'xl' => 1,
    ];

    protected ?string $heading = 'AI Verdict Distribution';

    protected ?string $description = 'How verification outcomes are currently distributed.';

    protected ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $counts = Cache::remember('filament.verdict_chart', 45, function (): array {
            $rows = VerificationLog::query()
                ->selectRaw("COALESCE(NULLIF(ai_verdict, ''), 'INCOMPLETE') as verdict")
                ->selectRaw('COUNT(*) as aggregate')
                ->groupBy(DB::raw("COALESCE(NULLIF(ai_verdict, ''), 'INCOMPLETE')"))
                ->pluck('aggregate', 'verdict');

            return [
                'SAFE' => (int) ($rows['SAFE'] ?? 0),
                'CAUTION' => (int) ($rows['CAUTION'] ?? 0),
                'DO_NOT_BUY' => (int) ($rows['DO_NOT_BUY'] ?? 0),
                'INCOMPLETE' => (int) ($rows['INCOMPLETE'] ?? 0),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Verifications',
                    'data' => [
                        $counts['SAFE'],
                        $counts['CAUTION'],
                        $counts['DO_NOT_BUY'],
                        $counts['INCOMPLETE'],
                    ],
                    'backgroundColor' => ['#16a34a', '#f59e0b', '#dc2626', '#6b7280'],
                ],
            ],
            'labels' => ['SAFE', 'CAUTION', 'DO_NOT_BUY', 'INCOMPLETE'],
        ];
    }
}
