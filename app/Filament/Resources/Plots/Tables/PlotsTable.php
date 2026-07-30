<?php

namespace App\Filament\Resources\Plots\Tables;

use App\Models\Nida;
use App\Models\Plot;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plot_reference')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner_nida')
                    ->label('Owner NIN')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.full_name')
                    ->label('Owner Name')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('linked_seller')
                    ->label('Linked seller')
                    ->state(function (Plot $record): string {
                        $seller = User::query()
                            ->where('role', 'seller')
                            ->where('nin', $record->owner_nida)
                            ->first();

                        return $seller?->email ?? '—';
                    })
                    ->toggleable(),
                TextColumn::make('region')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('district')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('land_use')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Active',
                        'danger' => 'Revoked',
                        'warning' => 'Under Review',
                        'gray' => 'Disputed',
                    ])
                    ->sortable(),
                IconColumn::make('double_allocation_flag')
                    ->label('Double Allocation')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('land_use')
                    ->options([
                        'Residential' => 'Residential',
                        'Commercial' => 'Commercial',
                        'Agricultural' => 'Agricultural',
                        'Industrial' => 'Industrial',
                        'Mixed' => 'Mixed',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Revoked' => 'Revoked',
                        'Under Review' => 'Under Review',
                        'Disputed' => 'Disputed',
                    ]),
                TernaryFilter::make('zoning_compliant')
                    ->label('Zoning Compliant'),
                TernaryFilter::make('double_allocation_flag')
                    ->label('Double Allocation Flag'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('assignOwner')
                    ->label('Assign owner')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->form([
                        Select::make('mode')
                            ->label('Assign by')
                            ->options([
                                'seller' => 'Existing seller account (uses their NIN)',
                                'nida' => 'NIDA / NIN directly',
                            ])
                            ->required()
                            ->live()
                            ->default('seller'),
                        Select::make('seller_id')
                            ->label('Seller')
                            ->searchable()
                            ->visible(fn ($get) => $get('mode') === 'seller')
                            ->required(fn ($get) => $get('mode') === 'seller')
                            ->getSearchResultsUsing(function (string $search): array {
                                return User::query()
                                    ->where('role', 'seller')
                                    ->whereNotNull('nin')
                                    ->where(function (Builder $q) use ($search): void {
                                        $q->where('email', 'like', "%{$search}%")
                                            ->orWhere('name', 'like', "%{$search}%")
                                            ->orWhere('nin', 'like', "%{$search}%");
                                    })
                                    ->orderBy('name')
                                    ->limit(40)
                                    ->get()
                                    ->mapWithKeys(fn (User $u): array => [
                                        $u->id => "{$u->name} — {$u->email} (NIN: {$u->nin})",
                                    ])
                                    ->all();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => User::query()->find($value)?->email),
                        Select::make('owner_nida')
                            ->label('Owner NIN')
                            ->searchable()
                            ->visible(fn ($get) => $get('mode') === 'nida')
                            ->required(fn ($get) => $get('mode') === 'nida')
                            ->getSearchResultsUsing(function (string $search): array {
                                return Nida::query()
                                    ->where('status', 'Active')
                                    ->where(function (Builder $q) use ($search): void {
                                        $q->where('nin', 'like', "%{$search}%")
                                            ->orWhere('first_name', 'like', "%{$search}%")
                                            ->orWhere('surname', 'like', "%{$search}%");
                                    })
                                    ->limit(40)
                                    ->get()
                                    ->mapWithKeys(fn (Nida $n): array => [
                                        $n->nin => "{$n->nin} — {$n->full_name}",
                                    ])
                                    ->all();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => $value),
                    ])
                    ->action(function (Plot $record, array $data): void {
                        $nin = null;

                        if (($data['mode'] ?? '') === 'seller') {
                            $seller = User::query()->find($data['seller_id'] ?? null);
                            $nin = $seller?->nin;
                        } else {
                            $nin = $data['owner_nida'] ?? null;
                        }

                        if (! $nin) {
                            Notification::make()
                                ->title('Could not resolve owner NIN')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['owner_nida' => $nin]);

                        Notification::make()
                            ->title('Plot owner updated')
                            ->body("owner_nida set to {$nin}")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
