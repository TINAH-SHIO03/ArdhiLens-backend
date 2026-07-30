<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\VerificationCertificates\VerificationCertificateResource;
use App\Models\VerificationCertificate;
use App\Services\AdminCertificateAssistService;
use App\Services\CertificateService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentCertificatesWidget extends TableWidget
{
    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public function table(Table $table): Table
    {
        $assist = app(AdminCertificateAssistService::class);

        return $table
            ->heading('Recently issued certificates')
            ->query(
                VerificationCertificate::query()
                    ->with('user')
                    ->latest('issued_at')
            )
            ->columns([
                TextColumn::make('certificate_number')->searchable(),
                TextColumn::make('certificate_type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state): string => $assist->typeLabel($state))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        CertificateService::TYPE_SELLER => 'success',
                        CertificateService::TYPE_BUYER => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')->label('Holder'),
                TextColumn::make('certificate_data.plot_reference')->label('Plot'),
                TextColumn::make('certificate_data.verdict')->label('Verdict')->badge(),
                TextColumn::make('issued_at')->dateTime(),
            ])
            ->recordUrl(fn (VerificationCertificate $record): string => VerificationCertificateResource::getUrl('view', ['record' => $record]))
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
