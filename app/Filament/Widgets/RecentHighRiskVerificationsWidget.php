<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VerificationLogs\VerificationLogResource;
use App\Models\VerificationLog;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentHighRiskVerificationsWidget extends TableWidget
{
    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'xl' => 2,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent High-Risk Verifications')
            ->query(
                VerificationLog::query()
                    ->with(['user:id,email', 'plot:id,plot_reference'])
                    ->where(function (Builder $query): Builder {
                        return $query
                            ->whereIn('ai_verdict', ['CAUTION', 'DO_NOT_BUY'])
                            ->orWhere('risk_score', '>=', 70);
                    })
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('plot.plot_reference')
                    ->label('Plot')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('ai_verdict')
                    ->label('Verdict')
                    ->colors([
                        'warning' => 'CAUTION',
                        'danger' => 'DO_NOT_BUY',
                        'gray' => 'INCOMPLETE',
                        'success' => 'SAFE',
                    ]),
                TextColumn::make('risk_score')
                    ->label('Risk')
                    ->placeholder('-')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Completed',
                        'danger' => 'Failed',
                        'warning' => 'Incomplete',
                    ]),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10])
            ->recordUrl(fn (VerificationLog $record): string => VerificationLogResource::getUrl('view', ['record' => $record]));
    }
}
