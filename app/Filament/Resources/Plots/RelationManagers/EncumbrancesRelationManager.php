<?php

namespace App\Filament\Resources\Plots\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EncumbrancesRelationManager extends RelationManager
{
    protected static string $relationship = 'encumbrances';

    protected static ?string $title = 'Encumbrances (Bank Loans)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('bank_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                DatePicker::make('registration_date')
                    ->required(),
                DatePicker::make('expiry_date')
                    ->rules(['nullable', 'date', 'after_or_equal:registration_date']),
                Select::make('status')
                    ->required()
                    ->default('Active')
                    ->options([
                        'Active' => 'Active',
                        'Discharged' => 'Discharged',
                        'Defaulted' => 'Defaulted',
                    ]),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bank_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('registration_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Active',
                        'gray' => 'Discharged',
                        'danger' => 'Defaulted',
                    ])
                    ->sortable(),
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
