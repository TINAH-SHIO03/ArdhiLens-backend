<?php

namespace App\Filament\Resources\Nidas\Tables;

use App\Models\Nida;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NidasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Always visible by default (core identifiers)
                TextColumn::make('nin')
                    ->label('NIN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->getStateUsing(fn (Nida $record): string => $record->full_name)
                    ->searchable(['first_name', 'middle_name', 'surname'])
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw("CONCAT(first_name, ' ', middle_name, ' ', surname) $direction")),

                // Everything else: toggleable + hidden by default
                BadgeColumn::make('gender')
                    ->label('Gender')
                    ->colors([
                        'primary' => 'M',
                        'success' => 'F',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('nationality')
                    ->label('Nationality')
                    ->colors([
                        'success' => 'Tanzanian',
                        'warning' => 'Resident',
                        'danger' => 'Refugee',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('marital_status')
                    ->label('Marital Status')
                    ->colors([
                        'gray' => 'Single',
                        'success' => 'Married',
                        'danger' => 'Divorced',
                        'warning' => 'Widowed',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('occupation')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('highest_education')
                    ->label('Education')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Suspended',
                        'gray' => 'Deceased',
                        'warning' => 'Pending',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Timestamps (usually hidden anyway)
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // You can add more hidden ones here if needed (e.g., addresses, parents)
                // Example:
                // TextColumn::make('res_region')
                //     ->toggleable(isToggledHiddenByDefault: true),
                // ... etc.
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('gender')
                    ->options(['M' => 'Male', 'F' => 'Female']),
                SelectFilter::make('nationality')
                    ->options([
                        'Tanzanian' => 'Tanzanian',
                        'Resident' => 'Resident',
                        'Refugee' => 'Refugee',
                    ]),
                SelectFilter::make('marital_status')
                    ->options([
                        'Single' => 'Single',
                        'Married' => 'Married',
                        'Widowed' => 'Widowed',
                        'Divorced' => 'Divorced',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Suspended' => 'Suspended',
                        'Deceased' => 'Deceased',
                        'Pending' => 'Pending',
                    ]),
                TernaryFilter::make('has_voter_id')
                    ->label('Has Voter ID?')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('voter_id'),
                        false: fn (Builder $query) => $query->whereNull('voter_id'),
                    ),
                Filter::make('date_of_birth')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('born_from')
                            ->label('Born From'),
                        \Filament\Forms\Components\DatePicker::make('born_until')
                            ->label('Born Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['born_from'], fn (Builder $query, $date): Builder => $query->whereDate('date_of_birth', '>=', $date))
                            ->when($data['born_until'], fn (Builder $query, $date): Builder => $query->whereDate('date_of_birth', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['born_from'] && ! $data['born_until']) {
                            return null;
                        }
                        return 'Born between ' . ($data['born_from'] ?? 'start') . ' and ' . ($data['born_until'] ?? 'now');
                    }),
                Filter::make('issued_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('issued_from')
                            ->label('Issued From'),
                        \Filament\Forms\Components\DatePicker::make('issued_until')
                            ->label('Issued Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['issued_from'], fn (Builder $query, $date): Builder => $query->whereDate('issued_at', '>=', $date))
                            ->when($data['issued_until'], fn (Builder $query, $date): Builder => $query->whereDate('issued_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['issued_from'] && ! $data['issued_until']) {
                            return null;
                        }
                        return 'Issued between ' . ($data['issued_from'] ?? 'start') . ' and ' . ($data['issued_until'] ?? 'now');
                    }),
                Filter::make('active')
                    ->label('Only Active')
                    ->query(fn (Builder $query): Builder => $query->active()),
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