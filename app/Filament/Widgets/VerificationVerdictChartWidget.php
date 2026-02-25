<?php

namespace App\Filament\Widgets;

use App\Models\VerificationLog;
use Filament\Widgets\ChartWidget;

class VerificationVerdictChartWidget extends ChartWidget
{
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected ?string $heading = 'AI Verdict Distribution';

    protected ?string $description = 'How verification outcomes are currently distributed.';

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $safe = VerificationLog::query()->where('ai_verdict', 'SAFE')->count();
        $caution = VerificationLog::query()->where('ai_verdict', 'CAUTION')->count();
        $doNotBuy = VerificationLog::query()->where('ai_verdict', 'DO_NOT_BUY')->count();
        $incomplete = VerificationLog::query()
            ->where('ai_verdict', 'INCOMPLETE')
            ->orWhereNull('ai_verdict')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Verifications',
                    'data' => [$safe, $caution, $doNotBuy, $incomplete],
                    'backgroundColor' => ['#16a34a', '#f59e0b', '#dc2626', '#6b7280'],
                ],
            ],
            'labels' => ['SAFE', 'CAUTION', 'DO_NOT_BUY', 'INCOMPLETE'],
        ];
    }
}
