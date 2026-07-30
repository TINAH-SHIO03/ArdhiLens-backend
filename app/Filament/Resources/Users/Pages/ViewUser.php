<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Nida;
use App\Models\Plot;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approveKyc')
                ->label('Approve KYC')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->record->isSeller()
                    && in_array($this->record->kyc_status, ['pending_review', 'needs_manual_review', 'required', 'rejected'], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var User $user */
                    $user = $this->record;
                    $user->update([
                        'kyc_status' => 'verified',
                        'verified_at' => now(),
                        'kyc_notes' => trim(($user->kyc_notes ? $user->kyc_notes."\n" : '').'Approved by admin '.now()->toDateTimeString()),
                    ]);

                    Notification::make()->title('Seller KYC approved')->success()->send();
                }),

            Action::make('rejectKyc')
                ->label('Reject KYC')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->isSeller()
                    && in_array($this->record->kyc_status, ['pending_review', 'needs_manual_review', 'required', 'verified'], true))
                ->form([
                    Textarea::make('reason')
                        ->label('Rejection reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'kyc_status' => 'rejected',
                        'kyc_notes' => $data['reason'],
                    ]);

                    Notification::make()->title('Seller KYC rejected')->warning()->send();
                }),

            Action::make('setNin')
                ->label('Set / change NIN')
                ->icon('heroicon-o-identification')
                ->color('warning')
                ->visible(fn (): bool => $this->record->isSeller() || $this->record->isBuyer())
                ->form([
                    TextInput::make('nin')
                        ->label('NIN')
                        ->required()
                        ->length(20)
                        ->default(fn () => $this->record->nin)
                        ->helperText('Must exist in NIDA registry. Plots with this owner_nida will link to the user.'),
                ])
                ->action(function (array $data): void {
                    $nin = trim($data['nin']);
                    if (! Nida::query()->where('nin', $nin)->exists()) {
                        Notification::make()
                            ->title('NIN not found in NIDA registry')
                            ->body('Create the NIDA record first under Nidas, then assign the NIN.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $taken = User::query()
                        ->where('nin', $nin)
                        ->where('id', '!=', $this->record->id)
                        ->exists();

                    if ($taken) {
                        Notification::make()
                            ->title('NIN already linked to another account')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->update(['nin' => $nin]);

                    $linked = Plot::query()->where('owner_nida', $nin)->count();

                    Notification::make()
                        ->title('NIN linked')
                        ->body($linked > 0
                            ? "{$linked} plot(s) now linked to this user."
                            : 'NIN saved. Assign plots to this NIN under Plots if needed.')
                        ->success()
                        ->send();
                }),

            Action::make('assignPlot')
                ->label('Assign plot to NIN')
                ->icon('heroicon-o-map')
                ->color('primary')
                ->visible(fn (): bool => filled($this->record->nin))
                ->form([
                    Select::make('plot_id')
                        ->label('Plot')
                        ->required()
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return Plot::query()
                                ->where(function (Builder $q) use ($search): void {
                                    $q->where('plot_reference', 'like', "%{$search}%")
                                        ->orWhere('region', 'like', "%{$search}%")
                                        ->orWhere('district', 'like', "%{$search}%");
                                })
                                ->orderBy('plot_reference')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Plot $plot): array => [
                                    $plot->id => "{$plot->plot_reference} — {$plot->region}/{$plot->district} (owner: {$plot->owner_nida})",
                                ])
                                ->all();
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => Plot::query()->find($value)?->plot_reference)
                        ->helperText('Sets plot owner_nida to this user’s NIN so it appears in their seller workspace.'),
                ])
                ->action(function (array $data): void {
                    $nin = $this->record->nin;
                    if (! $nin) {
                        Notification::make()->title('User has no NIN')->danger()->send();

                        return;
                    }

                    $updated = Plot::query()->whereKey($data['plot_id'])->update(['owner_nida' => $nin]);

                    if ($updated) {
                        Notification::make()
                            ->title('Plot assigned to user NIN')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Plot not found')
                            ->danger()
                            ->send();
                    }
                }),

            EditAction::make(),
        ];
    }
}
