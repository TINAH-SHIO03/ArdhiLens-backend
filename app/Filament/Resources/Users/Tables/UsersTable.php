<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Services\SellerKycDecisionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nin')
                    ->label('NIN')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('phone_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'buyer' => 'warning',
                        'seller' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('kyc_status')
                    ->label('KYC')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending_review', 'needs_manual_review', 'required' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('verified_at')
                    ->dateTime()
                    ->label('Verified')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'buyer' => 'Buyer',
                        'seller' => 'Seller',
                    ]),
                SelectFilter::make('kyc_status')
                    ->label('KYC Status')
                    ->options([
                        'none' => 'None',
                        'required' => 'Required',
                        'pending_review' => 'Pending review',
                        'needs_manual_review' => 'Needs manual review',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
                TernaryFilter::make('is_nida_verified')
                    ->label('NIDA Verified')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('verified_at')->whereNotNull('nin'),
                        false: fn ($query) => $query->whereNull('verified_at')->orWhereNull('nin'),
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approveKyc')
                    ->label('Approve KYC')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->isSeller()
                        && in_array($record->kyc_status, ['pending_review', 'needs_manual_review', 'required', 'rejected'], true))
                    ->requiresConfirmation()
                    ->modalHeading('Approve seller KYC')
                    ->modalDescription('Marks this seller as verified. Plots with matching owner_nida stay linked via their NIN.')
                    ->action(function (User $record): void {
                        app(SellerKycDecisionService::class)->approve($record);
                        Cache::forget('filament.land_snapshot_stats');

                        Notification::make()
                            ->title('Seller KYC approved')
                            ->success()
                            ->send();
                    }),
                Action::make('rejectKyc')
                    ->label('Reject KYC')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->isSeller()
                        && in_array($record->kyc_status, ['pending_review', 'needs_manual_review', 'required', 'verified'], true))
                    ->form([
                        Textarea::make('reason')
                            ->label('Rejection reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (User $record, array $data): void {
                        app(SellerKycDecisionService::class)->reject($record, $data['reason']);
                        Cache::forget('filament.land_snapshot_stats');

                        Notification::make()
                            ->title('Seller KYC rejected')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
