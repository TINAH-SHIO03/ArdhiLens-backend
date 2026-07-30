<?php

namespace App\Filament\Resources\VerificationCertificates;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\VerificationCertificates\Pages\ListVerificationCertificates;
use App\Filament\Resources\VerificationCertificates\Pages\ViewVerificationCertificate;
use App\Filament\Resources\VerificationLogs\VerificationLogResource;
use App\Models\VerificationCertificate;
use App\Services\AdminCertificateAssistService;
use App\Services\CertificateService;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VerificationCertificateResource extends Resource
{
    protected static ?string $model = VerificationCertificate::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string | \UnitEnum | null $navigationGroup = 'Certificates & Verification';

    protected static ?string $navigationLabel = 'Verification Certificates';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'certificate_number';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        $assist = app(AdminCertificateAssistService::class);

        return $schema
            ->components([
                Section::make('Certificate')
                    ->schema([
                        TextEntry::make('certificate_number')
                            ->copyable(),
                        TextEntry::make('certificate_type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => $assist->typeLabel($state))
                            ->color(fn (?string $state): string => match ($state) {
                                CertificateService::TYPE_SELLER => 'success',
                                CertificateService::TYPE_BUYER => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('certificate_data.certificate_title')
                            ->label('Title')
                            ->placeholder('—'),
                        TextEntry::make('issued_at')
                            ->dateTime(),
                        TextEntry::make('expires_at')
                            ->dateTime()
                            ->placeholder('—'),
                        IconEntry::make('is_valid')
                            ->label('Currently valid')
                            ->state(fn (VerificationCertificate $record): bool => $record->isValid())
                            ->boolean(),
                        TextEntry::make('verify_url')
                            ->label('Public verify URL')
                            ->state(fn (VerificationCertificate $record): string => $assist->verifyUrl($record))
                            ->copyable()
                            ->url(fn (VerificationCertificate $record): string => $assist->verifyUrl($record))
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Holder & plot')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Holder')
                            ->url(fn (VerificationCertificate $record): ?string => $record->user
                                ? UserResource::getUrl('view', ['record' => $record->user])
                                : null),
                        TextEntry::make('user.email')
                            ->label('Holder email')
                            ->placeholder('—'),
                        TextEntry::make('certificate_data.plot_reference')
                            ->label('Plot reference')
                            ->placeholder('—'),
                        TextEntry::make('certificate_data.plot_location')
                            ->label('Plot location')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('verification_log_id')
                            ->label('Verification log')
                            ->url(fn (VerificationCertificate $record): ?string => $record->verification_log_id
                                ? VerificationLogResource::getUrl('view', ['record' => $record->verification_log_id])
                                : null),
                    ])
                    ->columns(2),
                Section::make('Assessment snapshot')
                    ->schema([
                        TextEntry::make('certificate_data.verdict')
                            ->label('Verdict')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'SAFE' => 'success',
                                'CAUTION' => 'warning',
                                'DO_NOT_BUY' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('certificate_data.risk_score')
                            ->label('Risk score')
                            ->placeholder('—'),
                        IconEntry::make('certificate_data.geolocation_passed')
                            ->label('Geolocation passed')
                            ->boolean(),
                        IconEntry::make('certificate_data.nida_passed')
                            ->label('NIDA passed')
                            ->boolean(),
                        IconEntry::make('certificate_data.owner_link_passed')
                            ->label('Owner link passed')
                            ->boolean(),
                        TextEntry::make('certificate_data.email_sent_at')
                            ->label('Email sent at')
                            ->placeholder('Not sent')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Section::make('Files & integrity')
                    ->schema([
                        IconEntry::make('pdf_available')
                            ->label('PDF on disk')
                            ->state(fn (VerificationCertificate $record): bool => $assist->pdfExists($record))
                            ->boolean(),
                        TextEntry::make('pdf_path')
                            ->placeholder('—')
                            ->copyable(),
                        TextEntry::make('pdf_content_hash')
                            ->label('PDF hash')
                            ->placeholder('—')
                            ->copyable(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $assist = app(AdminCertificateAssistService::class);

        return $table
            ->columns([
                TextColumn::make('certificate_number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('certificate_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $assist->typeLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        CertificateService::TYPE_SELLER => 'success',
                        CertificateService::TYPE_BUYER => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Holder')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('certificate_data.plot_reference')
                    ->label('Plot')
                    ->searchable(),
                TextColumn::make('certificate_data.verdict')
                    ->label('Verdict')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'SAFE' => 'success',
                        'CAUTION' => 'warning',
                        'DO_NOT_BUY' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_valid')
                    ->label('Valid')
                    ->state(fn (VerificationCertificate $record): bool => $record->isValid())
                    ->boolean(),
                IconColumn::make('pdf_available')
                    ->label('PDF')
                    ->state(fn (VerificationCertificate $record): bool => $assist->pdfExists($record))
                    ->boolean(),
            ])
            ->defaultSort('issued_at', 'desc')
            ->filters([
                SelectFilter::make('certificate_type')
                    ->label('Type')
                    ->options([
                        CertificateService::TYPE_BUYER => 'Pre-Purchase',
                        CertificateService::TYPE_SELLER => 'Ownership Attestation',
                    ]),
                Filter::make('expiring_soon')
                    ->label('Expiring within 30 days')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->whereBetween('expires_at', [now(), now()->addDays(30)])),
                Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<', now())),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationCertificates::route('/'),
            'view' => ViewVerificationCertificate::route('/{record}'),
        ];
    }
}
