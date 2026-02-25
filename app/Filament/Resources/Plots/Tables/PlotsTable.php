<?php

namespace App\Filament\Resources\Plots\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plot_reference')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner_nida')
                    ->label('Owner NIN')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.full_name')
                    ->label('Owner Name')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('region')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('district')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('land_use')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Revoked',
                        'warning' => 'Under Review',
                        'gray' => 'Disputed',
                    ])
                    ->sortable(),
                IconColumn::make('double_allocation_flag')
                    ->label('Double Allocation')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('land_use')
                    ->options([
                        'Residential' => 'Residential',
                        'Commercial' => 'Commercial',
                        'Agricultural' => 'Agricultural',
                        'Industrial' => 'Industrial',
                        'Mixed' => 'Mixed',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Revoked' => 'Revoked',
                        'Under Review' => 'Under Review',
                        'Disputed' => 'Disputed',
                    ]),
                TernaryFilter::make('zoning_compliant')
                    ->label('Zoning Compliant'),
                TernaryFilter::make('double_allocation_flag')
                    ->label('Double Allocation Flag'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
