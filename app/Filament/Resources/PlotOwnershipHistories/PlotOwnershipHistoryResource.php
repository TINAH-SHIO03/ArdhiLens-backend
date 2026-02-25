<?php

namespace App\Filament\Resources\PlotOwnershipHistories;

use App\Filament\Resources\PlotOwnershipHistories\Pages\CreatePlotOwnershipHistory;
use App\Filament\Resources\PlotOwnershipHistories\Pages\EditPlotOwnershipHistory;
use App\Filament\Resources\PlotOwnershipHistories\Pages\ListPlotOwnershipHistories;
use App\Filament\Resources\PlotOwnershipHistories\Pages\ViewPlotOwnershipHistory;
use App\Models\Nida;
use App\Models\Plot;
use App\Models\PlotOwnershipHistory;
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
use Filament\Infolists\Components\RepeatableEntry;
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

class PlotOwnershipHistoryResource extends Resource
{
    protected static ?string $model = PlotOwnershipHistory::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $recordTitleAttribute = 'to_nida';

    protected static string | \UnitEnum | null $navigationGroup = 'Plot Operations';

    protected static ?string $navigationLabel = 'Ownership History';

    /**
     * @var array<int, string>
     */
    protected static array $ownershipChainCache = [];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plot & Owner Verification')
                    ->schema([
                        TextInput::make('owner_nida_check')
                            ->label('Current Owner NIN (Verify First)')
                            ->required()
                            ->dehydrated(false)
                            ->maxLength(20)
                            ->default(fn (?PlotOwnershipHistory $record): ?string => $record?->plot?->owner_nida)
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
                Section::make('Transfer Details')
                    ->schema([
                        Select::make('from_nida')
                            ->label('From NIN')
                            ->relationship(name: 'previousOwner', titleAttribute: 'nin')
                            ->searchable(['nin', 'first_name', 'middle_name', 'surname'])
                            ->getOptionLabelFromRecordUsing(fn (Nida $record): string => "{$record->nin} - {$record->full_name}")
                            ->helperText('Leave empty if this is the first registration.'),
                        Select::make('to_nida')
                            ->label('To NIN')
                            ->relationship(name: 'newOwner', titleAttribute: 'nin')
                            ->searchable(['nin', 'first_name', 'middle_name', 'surname'])
                            ->getOptionLabelFromRecordUsing(fn (Nida $record): string => "{$record->nin} - {$record->full_name}")
                            ->required()
                            ->rules(['different:from_nida']),
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
                            ->label('Current Owner NIN'),
                        TextEntry::make('from_nida')
                            ->label('From NIN')
                            ->placeholder('-'),
                        TextEntry::make('to_nida')
                            ->label('To NIN'),
                        TextEntry::make('transfer_date')
                            ->date(),
                        TextEntry::make('transfer_reason')
                            ->badge(),
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Ownership Tree')
                    ->description('Complete ownership flow from origin to current owner, including dates.')
                    ->schema([
                        RepeatableEntry::make('ownership_timeline')
                            ->label('Full History Timeline')
                            ->state(fn (PlotOwnershipHistory $record): array => static::getOwnershipTimelineItems($record))
                            ->contained()
                            ->grid(1)
                            ->schema([
                                TextEntry::make('stage')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Origin' => 'gray',
                                        'Transfer' => 'primary',
                                        'Current' => 'success',
                                        default => 'warning',
                                    }),
                                TextEntry::make('date')
                                    ->label('Date')
                                    ->badge()
                                    ->color('warning'),
                                TextEntry::make('owner')
                                    ->label('Owner')
                                    ->weight('bold')
                                    ->wrap()
                                    ->columnSpanFull(),
                                TextEntry::make('reason')
                                    ->label('Reason')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
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
                    ->label('Current Owner NIN')
                    ->searchable()
                    ->sortable(),
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
                TextColumn::make('full_ownership_chain')
                    ->label('Full Chain')
                    ->state(fn (PlotOwnershipHistory $record): string => static::getFullOwnershipChain($record))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('owner_nida')
                    ->form([
                        TextInput::make('owner_nida')
                            ->label('Current Owner NIN'),
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

    protected static function getFullOwnershipChain(PlotOwnershipHistory $record): string
    {
        if (blank($record->plot_id)) {
            return 'No plot linked';
        }

        if (array_key_exists($record->plot_id, static::$ownershipChainCache)) {
            return static::$ownershipChainCache[$record->plot_id];
        }

        $plot = Plot::query()
            ->with('owner')
            ->find($record->plot_id);

        if (! $plot) {
            return static::$ownershipChainCache[$record->plot_id] = 'Plot not found';
        }

        $histories = PlotOwnershipHistory::query()
            ->with(['previousOwner', 'newOwner'])
            ->where('plot_id', $record->plot_id)
            ->orderBy('transfer_date')
            ->orderBy('id')
            ->get();

        if ($histories->isEmpty()) {
            $owner = static::formatOwner($plot->owner_nida, $plot->owner);
            $date = $plot->created_at?->format('Y-m-d') ?? 'unknown';

            return static::$ownershipChainCache[$record->plot_id] = "{$owner} [{$date}]";
        }

        $chain = [];
        $first = $histories->first();
        $firstDate = $first?->transfer_date?->format('Y-m-d') ?? 'unknown';
        $originOwner = filled($first?->from_nida)
            ? static::formatOwner($first->from_nida, $first->previousOwner)
            : static::formatOwner($plot->owner_nida, $plot->owner);
        $chain[] = "{$originOwner} [before {$firstDate}]";

        /** @var PlotOwnershipHistory $history */
        foreach ($histories as $history) {
            $date = $history->transfer_date?->format('Y-m-d') ?? 'unknown';
            $toOwner = static::formatOwner($history->to_nida, $history->newOwner);
            $chain[] = "{$toOwner} [{$date}]";
        }

        $currentOwner = static::formatOwner($plot->owner_nida, $plot->owner);
        $lastOwnerInHistory = static::formatOwner($histories->last()->to_nida, $histories->last()->newOwner);

        if ($currentOwner !== $lastOwnerInHistory) {
            $chain[] = "{$currentOwner} [current]";
        }

        return static::$ownershipChainCache[$record->plot_id] = implode(' -> ', $chain);
    }

    /**
     * @return array<int, array{stage: string, owner: string, date: string, reason: string}>
     */
    protected static function getOwnershipTimelineItems(PlotOwnershipHistory $record): array
    {
        if (blank($record->plot_id)) {
            return [[
                'stage' => 'Unavailable',
                'owner' => 'No plot linked',
                'date' => '-',
                'reason' => '-',
            ]];
        }

        $plot = Plot::query()
            ->with('owner')
            ->find($record->plot_id);

        if (! $plot) {
            return [[
                'stage' => 'Unavailable',
                'owner' => 'Plot not found',
                'date' => '-',
                'reason' => '-',
            ]];
        }

        $histories = PlotOwnershipHistory::query()
            ->with(['previousOwner', 'newOwner'])
            ->where('plot_id', $record->plot_id)
            ->orderBy('transfer_date')
            ->orderBy('id')
            ->get();

        $timeline = [];

        if ($histories->isEmpty()) {
            $owner = static::formatOwner($plot->owner_nida, $plot->owner);
            $date = $plot->created_at?->format('Y-m-d') ?? 'unknown';
            $timeline[] = [
                'stage' => 'Origin',
                'owner' => $owner,
                'date' => $date,
                'reason' => 'Initial registration',
            ];
            $timeline[] = [
                'stage' => 'Current',
                'owner' => $owner,
                'date' => $date,
                'reason' => 'Current owner',
            ];

            return $timeline;
        }

        $first = $histories->first();
        $firstDate = $first?->transfer_date?->format('Y-m-d') ?? 'unknown';
        $origin = filled($first?->from_nida)
            ? static::formatOwner($first->from_nida, $first->previousOwner)
            : static::formatOwner($plot->owner_nida, $plot->owner);

        $timeline[] = [
            'stage' => 'Origin',
            'owner' => $origin,
            'date' => "Before {$firstDate}",
            'reason' => 'Ownership origin',
        ];

        /** @var PlotOwnershipHistory $history */
        foreach ($histories as $history) {
            $date = $history->transfer_date?->format('Y-m-d') ?? '-';
            $toOwner = static::formatOwner($history->to_nida, $history->newOwner);
            $timeline[] = [
                'stage' => 'Transfer',
                'owner' => $toOwner,
                'date' => $date,
                'reason' => $history->transfer_reason,
            ];
        }

        $currentOwner = static::formatOwner($plot->owner_nida, $plot->owner);
        $lastOwnerInHistory = static::formatOwner($histories->last()->to_nida, $histories->last()->newOwner);

        if ($currentOwner !== $lastOwnerInHistory) {
            $timeline[] = [
                'stage' => 'Current',
                'owner' => $currentOwner,
                'date' => 'Current',
                'reason' => 'Current owner',
            ];
        }

        return $timeline;
    }

    protected static function formatOwner(?string $nin, ?Nida $nida = null): string
    {
        if (blank($nin)) {
            return 'Unknown Owner';
        }

        if ($nida) {
            return "{$nida->full_name} ({$nin})";
        }

        return $nin;
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
            'index' => ListPlotOwnershipHistories::route('/'),
            'create' => CreatePlotOwnershipHistory::route('/create'),
            'view' => ViewPlotOwnershipHistory::route('/{record}'),
            'edit' => EditPlotOwnershipHistory::route('/{record}/edit'),
        ];
    }
}
