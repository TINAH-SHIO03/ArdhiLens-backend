<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;           // ← Add this import
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nin')
                    ->label('NIN')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('phone_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'buyer' => 'warning',
                        'seller' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('verified_at')
                    ->dateTime()
                    ->label('Verified')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'buyer' => 'Buyer',
                        'seller' => 'Seller',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
                TernaryFilter::make('is_nida_verified')
                    ->label('NIDA Verified')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('verified_at')->whereNotNull('nin'),
                        false: fn ($query) => $query->whereNull('verified_at')->orWhereNull('nin'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),     // ← Added: opens the view/infolist page
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}