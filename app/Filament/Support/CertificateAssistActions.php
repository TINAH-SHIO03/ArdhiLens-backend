<?php

namespace App\Filament\Support;

use App\Filament\Resources\VerificationCertificates\VerificationCertificateResource;
use App\Models\VerificationCertificate;
use App\Models\VerificationLog;
use App\Services\AdminCertificateAssistService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateAssistActions
{
    /**
     * @return array<int, Action>
     */
    public static function forCertificate(VerificationCertificate $certificate): array
    {
        $assist = app(AdminCertificateAssistService::class);

        return [
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn (): bool => $assist->pdfExists($certificate))
                ->action(function () use ($certificate, $assist): StreamedResponse {
                    abort_unless($assist->pdfExists($certificate), 404);

                    return Storage::disk('local')->download(
                        $certificate->pdf_path,
                        "certificate_{$certificate->certificate_number}.pdf",
                    );
                }),

            Action::make('openVerifyPage')
                ->label('Open verify page')
                ->icon('heroicon-o-qr-code')
                ->color('gray')
                ->url(fn (): string => $assist->verifyUrl($certificate))
                ->openUrlInNewTab(),

            Action::make('regeneratePdf')
                ->label('Regenerate PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Rebuilds the signed PDF file and emails the holder if configured.')
                ->action(function () use ($certificate, $assist): void {
                    $assist->regeneratePdf($certificate, auth()->user());

                    Notification::make()
                        ->title('Certificate PDF regenerated')
                        ->success()
                        ->send();
                }),

            Action::make('resendEmail')
                ->label('Resend email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $assist->pdfExists($certificate))
                ->action(function () use ($certificate, $assist): void {
                    $sent = $assist->resendEmail($certificate, auth()->user());

                    Notification::make()
                        ->title($sent ? 'Certificate email sent' : 'Email could not be sent')
                        ->color($sent ? 'success' : 'warning')
                        ->send();
                }),
        ];
    }

    /**
     * @return array<int, Action>
     */
    public static function forVerificationLog(VerificationLog $log): array
    {
        $assist = app(AdminCertificateAssistService::class);

        return [
            Action::make('issueCertificate')
                ->label('Issue certificate')
                ->icon('heroicon-o-document-check')
                ->color('success')
                ->visible(function () use ($log, $assist): bool {
                    return $assist->eligibilityForLog($log)['eligible'];
                })
                ->requiresConfirmation()
                ->modalHeading('Issue verification certificate')
                ->modalDescription('Creates the certificate and PDF using the same rules as the mobile verification flow.')
                ->action(function () use ($log, $assist): void {
                    try {
                        $certificate = $assist->issueForLog($log, auth()->user());
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Could not issue certificate')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Certificate issued')
                        ->body($certificate->certificate_number)
                        ->success()
                        ->send();

                    return redirect(VerificationCertificateResource::getUrl('view', ['record' => $certificate]));
                }),

            Action::make('viewCertificate')
                ->label('View certificate')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->visible(fn (): bool => $log->certificate !== null)
                ->url(fn (): string => VerificationCertificateResource::getUrl('view', [
                    'record' => $log->certificate,
                ])),
        ];
    }
}
