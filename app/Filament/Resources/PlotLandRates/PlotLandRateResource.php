<?php

namespace App\Filament\Resources\PlotLandRates;

use App\Filament\Resources\PlotLandRates\Pages\CreatePlotLandRate;
use App\Filament\Resources\PlotLandRates\Pages\EditPlotLandRate;
use App\Filament\Resources\PlotLandRates\Pages\ListPlotLandRates;
use App\Filament\Resources\PlotLandRates\Pages\ViewPlotLandRate;
use App\Models\Plot;
use App\Models\PlotLandRate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

class PlotLandRateResource extends Resource
{
    protected static ?string $model = PlotLandRate::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $recordTitleAttribute = 'receipt_number';

    protected static string | \UnitEnum | null $navigationGroup = 'Plot Operations';

    protected static ?string $navigationLabel = 'Plot Land Rates';

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
                            ->default(fn (?PlotLandRate $record): ?string => $record?->plot?->owner_nida)
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
                Section::make('Payment Details')
                    ->schema([
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
                        TextEntry::make('amount_paid')
                            ->numeric(decimalPlaces: 2),
                        TextEntry::make('payment_date')
                            ->date(),
                        TextEntry::make('period_from')
                            ->date(),
                        TextEntry::make('period_to')
                            ->date(),
                        TextEntry::make('receipt_number')
                            ->placeholder('-'),
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
                TextColumn::make('amount_paid')
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
            'index' => ListPlotLandRates::route('/'),
            'create' => CreatePlotLandRate::route('/create'),
            'view' => ViewPlotLandRate::route('/{record}'),
            'edit' => EditPlotLandRate::route('/{record}/edit'),
        ];
    }
}
