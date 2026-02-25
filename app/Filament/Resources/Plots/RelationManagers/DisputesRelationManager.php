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

class DisputesRelationManager extends RelationManager
{
    protected static string $relationship = 'disputes';

    protected static ?string $title = 'Disputes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dispute_type')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('court_case_number')
                    ->maxLength(255),
                DatePicker::make('filing_date')
                    ->required(),
                DatePicker::make('resolved_date')
                    ->rules(['nullable', 'date', 'after_or_equal:filing_date']),
                Select::make('status')
                    ->required()
                    ->default('Ongoing')
                    ->options([
                        'Ongoing' => 'Ongoing',
                        'Resolved' => 'Resolved',
                        'Withdrawn' => 'Withdrawn',
                    ]),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dispute_type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('court_case_number')
                    ->label('Case No.')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('filing_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('resolved_date')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'Ongoing',
                        'success' => 'Resolved',
                        'gray' => 'Withdrawn',
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
