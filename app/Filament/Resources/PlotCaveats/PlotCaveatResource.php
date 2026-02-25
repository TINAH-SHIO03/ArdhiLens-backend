<?php

namespace App\Filament\Resources\PlotCaveats;

use App\Filament\Resources\PlotCaveats\Pages\CreatePlotCaveat;
use App\Filament\Resources\PlotCaveats\Pages\EditPlotCaveat;
use App\Filament\Resources\PlotCaveats\Pages\ListPlotCaveats;
use App\Filament\Resources\PlotCaveats\Pages\ViewPlotCaveat;
use App\Models\Plot;
use App\Models\PlotCaveat;
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

class PlotCaveatResource extends Resource
{
    protected static ?string $model = PlotCaveat::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $recordTitleAttribute = 'caveat_by';

    protected static string | \UnitEnum | null $navigationGroup = 'Plot Operations';

    protected static ?string $navigationLabel = 'Plot Caveats';

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
                            ->default(fn (?PlotCaveat $record): ?string => $record?->plot?->owner_nida)
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
                Section::make('Caveat Details')
                    ->schema([
                        TextInput::make('caveat_by')
                            ->required()
                            ->maxLength(255),
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
                        Textarea::make('reason')
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
                        TextEntry::make('caveat_by')
                            ->label('Caveat By'),
                        TextEntry::make('registration_date')
                            ->date(),
                        TextEntry::make('expiry_date')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('reason')
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
            'index' => ListPlotCaveats::route('/'),
            'create' => CreatePlotCaveat::route('/create'),
            'view' => ViewPlotCaveat::route('/{record}'),
            'edit' => EditPlotCaveat::route('/{record}/edit'),
        ];
    }
}
