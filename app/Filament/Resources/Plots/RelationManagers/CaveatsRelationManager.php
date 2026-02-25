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
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CaveatsRelationManager extends RelationManager
{
    protected static string $relationship = 'caveats';

    protected static ?string $title = 'Caveats';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('caveat_by')
                    ->required()
                    ->maxLength(255),
                Textarea::make('reason')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                DatePicker::make('registration_date')
                    ->required(),
                DatePicker::make('expiry_date')
                    ->rules(['nullable', 'date', 'after_or_equal:registration_date']),
                Select::make('status')
                    ->required()
                    ->default('Active')
                    ->options([
                        'Active' => 'Active',
                        'Lifted' => 'Lifted',
                    ]),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('caveat_by')
                    ->label('Caveat By')
                    ->searchable()
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
                        'warning' => 'Active',
                        'success' => 'Lifted',
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
