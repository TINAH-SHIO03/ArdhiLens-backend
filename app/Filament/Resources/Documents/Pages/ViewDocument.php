<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;
use App\Services\DocumentAuthenticityService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download file')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn (): bool => filled($this->record->file_path)
                    && Storage::disk('local')->exists($this->record->file_path))
                ->action(function (): StreamedResponse {
                    /** @var Document $document */
                    $document = $this->record;

                    return Storage::disk('local')->download($document->file_path, $document->original_name);
                }),

            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => ! in_array($this->record->review_status, ['approved', 'auto_approved'], true))
                ->form([
                    Textarea::make('notes')
                        ->label('Review notes')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->reviewDocument('approved', $data['notes'] ?? null);
                }),

            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('notes')
                        ->label('Rejection reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->reviewDocument('rejected', $data['notes']);
                }),

            Action::make('flag')
                ->label('Flag for review')
                ->icon('heroicon-o-flag')
                ->color('warning')
                ->form([
                    Textarea::make('notes')
                        ->label('Flag reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->reviewDocument('flagged', $data['notes']);
                }),
        ];
    }

    private function reviewDocument(string $status, ?string $notes): void
    {
        /** @var Document $document */
        $document = $this->record;

        app(DocumentAuthenticityService::class)->markReviewed(
            $document,
            (int) auth()->id(),
            $status,
            $notes,
        );

        $this->record->refresh();

        Notification::make()
            ->title('Document review updated')
            ->body(ucfirst($status))
            ->success()
            ->send();
    }
}
