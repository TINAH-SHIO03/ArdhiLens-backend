<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VerificationCertificates\VerificationCertificateResource;
use App\Models\VerificationCertificate;
use App\Services\AdminCertificateAssistService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpiringCertificatesWidget extends TableWidget
{
    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 8;

    public function table(Table $table): Table
    {
        $assist = app(AdminCertificateAssistService::class);

        return $table
            ->heading('Certificates expiring within 30 days')
            ->query(
                VerificationCertificate::query()
                    ->with('user')
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays(30)])
                    ->orderBy('expires_at')
            )
            ->columns([
                TextColumn::make('certificate_number'),
                TextColumn::make('certificate_type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state): string => $assist->typeLabel($state))
                    ->badge(),
                TextColumn::make('user.name')->label('Holder'),
                TextColumn::make('certificate_data.plot_reference')->label('Plot'),
                TextColumn::make('expires_at')->dateTime()->color('warning'),
            ])
            ->recordUrl(fn (VerificationCertificate $record): string => VerificationCertificateResource::getUrl('view', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
