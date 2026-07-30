<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PlotOwnershipHistories\PlotOwnershipHistoryResource;
use App\Models\PlotOwnershipHistory;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOwnershipTransfersWidget extends TableWidget
{
    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Ownership Transfers')
            ->query(
                PlotOwnershipHistory::query()
                    ->with('plot:id,plot_reference')
                    ->latest('transfer_date')
                    ->latest('id')
            )
            ->columns([
                TextColumn::make('transfer_date')
                    ->label('Transfer Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('plot.plot_reference')
                    ->label('Plot Ref')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('from_nida')
                    ->label('From NIN')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('to_nida')
                    ->label('To NIN')
                    ->searchable(),
                BadgeColumn::make('transfer_reason')
                    ->label('Reason'),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10])
            ->recordUrl(fn (PlotOwnershipHistory $record): string => PlotOwnershipHistoryResource::getUrl('view', ['record' => $record]));
    }
}
