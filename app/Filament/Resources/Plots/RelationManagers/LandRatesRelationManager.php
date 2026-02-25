<?php

namespace App\Filament\Resources\Plots\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LandRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'landRates';

    protected static ?string $title = 'Land Rates';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount_paid')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                DatePicker::make('payment_date')
                    ->required(),
                DatePicker::make('period_from')
                    ->required(),
                DatePicker::make('period_to')
                    ->required()
                    ->rules(['date', 'after_or_equal:period_from']),
                TextInput::make('receipt_number')
                    ->maxLength(255),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_to')
                    ->date()
                    ->sortable(),
                TextColumn::make('receipt_number')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
