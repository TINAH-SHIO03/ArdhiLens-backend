<?php

namespace App\Filament\Resources\VerificationLogs\Pages;

use App\Filament\Resources\VerificationLogs\VerificationLogResource;
use App\Filament\Support\CertificateAssistActions;
use App\Services\AdminCertificateAssistService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewVerificationLog extends ViewRecord
{
    protected static string $resource = VerificationLogResource::class;

    protected function getHeaderActions(): array
    {
        $this->record->loadMissing('certificate');

        return array_merge(
            CertificateAssistActions::forVerificationLog($this->record),
            [
                Action::make('saveAdminNotes')
                    ->label('Admin notes')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->fillForm(fn (): array => [
                        'admin_notes' => $this->record->admin_notes,
                    ])
                    ->form([
                        Textarea::make('admin_notes')
                            ->label('Internal admin notes')
                            ->rows(5)
                            ->helperText('Visible only in the admin panel. Not shown to app users.'),
                    ])
                    ->action(function (array $data): void {
                        $this->record->update([
                            'admin_notes' => $data['admin_notes'] ?: null,
                        ]);

                        Notification::make()
                            ->title('Admin notes saved')
                            ->success()
                            ->send();
                    }),

                Action::make('explainCertificateEligibility')
                    ->label('Certificate eligibility')
                    ->icon('heroicon-o-information-circle')
                    ->color('info')
                    ->visible(fn (): bool => $this->record->certificate === null)
                    ->modalHeading('Certificate eligibility')
                    ->modalContent(function (): \Illuminate\Contracts\View\View {
                        $check = app(AdminCertificateAssistService::class)->eligibilityForLog($this->record);

                        return view('filament.modals.certificate-eligibility', [
                            'eligible' => $check['eligible'],
                            'reason' => $check['reason'],
                            'mode' => app(AdminCertificateAssistService::class)->typeLabel($check['mode']),
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ],
        );
    }
}
