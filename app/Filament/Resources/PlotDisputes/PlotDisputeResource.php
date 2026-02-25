<?php

namespace App\Filament\Resources\PlotDisputes;

use App\Filament\Resources\PlotDisputes\Pages\CreatePlotDispute;
use App\Filament\Resources\PlotDisputes\Pages\EditPlotDispute;
use App\Filament\Resources\PlotDisputes\Pages\ListPlotDisputes;
use App\Filament\Resources\PlotDisputes\Pages\ViewPlotDispute;
use App\Models\Plot;
use App\Models\PlotDispute;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

class PlotDisputeResource extends Resource
{
    protected static ?string $model = PlotDispute::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static ?string $recordTitleAttribute = 'dispute_type';

    protected static string | \UnitEnum | null $navigationGroup = 'Plot Operations';

    protected static ?string $navigationLabel = 'Plot Disputes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Owner Verification')
                    ->schema([
                        TextInput::make('owner_nida_check')
                            ->label('Owner NIN (Verify First)')
                            ->required()
                            ->dehydrated(false)
                            ->maxLength(20)
                            ->default(fn (?PlotDispute $record): ?string => $record?->plot?->owner_nida)
                            ->live(debounce: 500)
                            ->afterStateUpdated(fn (Set $set) => $set('plot_id', null))
                            ->rules([
                                Rule::exists('nidas', 'nin')
                                    ->where(fn (QueryBuilder $query): QueryBuilder => $query
                                        ->where('status', 'Active')
                                        ->whereNull('deleted_at')),
                            ]),
                        Select::make('plot_id')
                            ->label('Plot')
                            ->required()
                            ->searchable()
                            ->options(fn (Get $get): array => Plot::query()
                                ->when(
                                    filled($get('owner_nida_check')),
                                    fn (Builder $query): Builder => $query->where('owner_nida', $get('owner_nida_check')),
                                    fn (Builder $query): Builder => $query->whereRaw('1 = 0')
                                )
                                ->orderBy('plot_reference')
                                ->pluck('plot_reference', 'id')
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => Plot::query()->whereKey($value)->value('plot_reference'))
                            ->disabled(fn (Get $get): bool => blank($get('owner_nida_check')))
                            ->rules([
                                fn (Get $get) => Rule::exists('plots', 'id')
                                    ->where(fn (QueryBuilder $query): QueryBuilder => $query->where('owner_nida', $get('owner_nida_check'))),
                            ]),
                    ])
                    ->columns(2),
                Section::make('Dispute Details')
                    ->schema([
                        TextInput::make('dispute_type')
                            ->required()
                            ->maxLength(255),
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
                        Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Record')
                    ->schema([
                        TextEntry::make('plot.plot_reference')
                            ->label('Plot Reference'),
                        TextEntry::make('plot.owner_nida')
                            ->label('Owner NIN'),
                        TextEntry::make('dispute_type'),
                        TextEntry::make('court_case_number')
                            ->placeholder('-'),
                        TextEntry::make('filing_date')
                            ->date(),
                        TextEntry::make('resolved_date')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plot.plot_reference')
                    ->label('Plot Ref')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plot.owner_nida')
                    ->label('Owner NIN')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dispute_type')
                    ->searchable()
                    ->sortable(),
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
            ->filters([
                Filter::make('owner_nida')
                    ->form([
                        TextInput::make('owner_nida')
                            ->label('Owner NIN'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['owner_nida'] ?? null,
                            fn (Builder $query, string $ownerNin): Builder => $query->whereHas(
                                'plot',
                                fn (Builder $plotQuery): Builder => $plotQuery->where('owner_nida', $ownerNin)
                            )
                        );
                    }),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlotDisputes::route('/'),
            'create' => CreatePlotDispute::route('/create'),
            'view' => ViewPlotDispute::route('/{record}'),
            'edit' => EditPlotDispute::route('/{record}/edit'),
        ];
    }
}
