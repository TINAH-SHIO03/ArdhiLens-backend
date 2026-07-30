<?php

namespace App\Filament\Resources\PurchaseInterests;

use App\Filament\Resources\Plots\PlotResource;
use App\Filament\Resources\PurchaseInterests\Pages\ListPurchaseInterests;
use App\Filament\Resources\PurchaseInterests\Pages\ViewPurchaseInterest;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\VerificationLogs\VerificationLogResource;
use App\Models\PurchaseInterest;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseInterestResource extends Resource
{
    protected static ?string $model = PurchaseInterest::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string | \UnitEnum | null $navigationGroup = 'Certificates & Verification';

    protected static ?string $navigationLabel = 'Purchase Interests';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'plot_reference';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Interest')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                PurchaseInterest::STATUS_PENDING => 'warning',
                                PurchaseInterest::STATUS_ACCEPTED => 'success',
                                PurchaseInterest::STATUS_DECLINED => 'danger',
                                PurchaseInterest::STATUS_CONTACTED => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('plot_reference')
                            ->placeholder('—'),
                        TextEntry::make('buyer_message')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->wrap(),
                        TextEntry::make('seller_reply')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->wrap(),
                        TextEntry::make('responded_at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Parties')
                    ->schema([
                        TextEntry::make('buyer.name')
                            ->label('Buyer')
                            ->url(fn (PurchaseInterest $record): ?string => $record->buyer
                                ? UserResource::getUrl('view', ['record' => $record->buyer])
                                : null),
                        TextEntry::make('buyer.email')
                            ->label('Buyer email')
                            ->placeholder('—'),
                        TextEntry::make('seller.name')
                            ->label('Seller')
                            ->url(fn (PurchaseInterest $record): ?string => $record->seller
                                ? UserResource::getUrl('view', ['record' => $record->seller])
                                : null),
                        TextEntry::make('seller.email')
                            ->label('Seller email')
                            ->placeholder('—'),
                        TextEntry::make('plot.plot_reference')
                            ->label('Plot')
                            ->placeholder('—')
                            ->url(fn (PurchaseInterest $record): ?string => $record->plot
                                ? PlotResource::getUrl('view', ['record' => $record->plot])
                                : null),
                        TextEntry::make('verification_log_id')
                            ->label('Verification log')
                            ->placeholder('—')
                            ->url(fn (PurchaseInterest $record): ?string => $record->verification_log_id
                                ? VerificationLogResource::getUrl('view', ['record' => $record->verification_log_id])
                                : null),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plot_reference')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('buyer.name')
                    ->label('Buyer')
                    ->searchable(),
                TextColumn::make('seller.name')
                    ->label('Seller')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PurchaseInterest::STATUS_PENDING => 'warning',
                        PurchaseInterest::STATUS_ACCEPTED => 'success',
                        PurchaseInterest::STATUS_DECLINED => 'danger',
                        PurchaseInterest::STATUS_CONTACTED => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('responded_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        PurchaseInterest::STATUS_PENDING => 'Pending',
                        PurchaseInterest::STATUS_ACCEPTED => 'Accepted',
                        PurchaseInterest::STATUS_DECLINED => 'Declined',
                        PurchaseInterest::STATUS_CONTACTED => 'Contacted',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseInterests::route('/'),
            'view' => ViewPurchaseInterest::route('/{record}'),
        ];
    }
}
