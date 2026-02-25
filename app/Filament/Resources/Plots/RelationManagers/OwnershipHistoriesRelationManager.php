<?php

namespace App\Filament\Resources\Plots\RelationManagers;

use App\Models\Nida;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OwnershipHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'ownershipHistories';

    protected static ?string $title = 'Ownership History';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('from_nida')
                    ->label('Previous Owner (NIN)')
                    ->relationship(name: 'previousOwner', titleAttribute: 'nin')
                    ->searchable(['nin', 'first_name', 'middle_name', 'surname'])
                    ->getOptionLabelFromRecordUsing(fn (Nida $record): string => "{$record->nin} - {$record->full_name}")
                    ->helperText('Leave empty if this is the first ownership record for the plot.'),
                Select::make('to_nida')
                    ->label('New Owner (NIN)')
                    ->relationship(name: 'newOwner', titleAttribute: 'nin')
                    ->searchable(['nin', 'first_name', 'middle_name', 'surname'])
                    ->getOptionLabelFromRecordUsing(fn (Nida $record): string => "{$record->nin} - {$record->full_name}")
                    ->required(),
                DatePicker::make('transfer_date')
                    ->required(),
                Select::make('transfer_reason')
                    ->required()
                    ->default('Sale')
                    ->options([
                        'Sale' => 'Sale',
                        'Inheritance' => 'Inheritance',
                        'Gift' => 'Gift',
                        'Court Order' => 'Court Order',
                    ]),
                Textarea::make('notes')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('from_nida')
                    ->label('From NIN')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('to_nida')
                    ->label('To NIN')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transfer_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('transfer_reason')
                    ->badge()
                    ->sortable(),
                TextColumn::make('notes')
                    ->limit(50)
                    ->placeholder('-'),
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
