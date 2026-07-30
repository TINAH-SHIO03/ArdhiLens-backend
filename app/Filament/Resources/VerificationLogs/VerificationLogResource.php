<?php

namespace App\Filament\Resources\VerificationLogs;

use App\Filament\Resources\VerificationCertificates\VerificationCertificateResource;
use App\Filament\Resources\VerificationLogs\Pages\ListVerificationLogs;
use App\Filament\Resources\VerificationLogs\Pages\ViewVerificationLog;
use App\Models\VerificationLog;
use App\Services\AdminCertificateAssistService;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VerificationLogResource extends Resource
{
    protected static ?string $model = VerificationLog::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string | \UnitEnum | null $navigationGroup = 'Certificates & Verification';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Verification Logs';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        // Logs are system records; keep resource read-only.
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Verification Context')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Completed' => 'success',
                                'Failed' => 'danger',
                                'Incomplete' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('user.name')
                            ->label('User')
                            ->placeholder('-'),
                        TextEntry::make('user.email')
                            ->label('User Email')
                            ->placeholder('-'),
                        TextEntry::make('plot.plot_reference')
                            ->label('Plot Reference')
                            ->placeholder('-'),
                        TextEntry::make('plot.owner_nida')
                            ->label('Plot Owner NIN')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Step Results')
                    ->schema([
                        IconEntry::make('geolocation_passed')
                            ->label('Geolocation Passed')
                            ->boolean(),
                        IconEntry::make('nida_passed')
                            ->label('NIDA Passed')
                            ->boolean(),
                        IconEntry::make('certificate_passed')
                            ->label('Certificate Passed')
                            ->boolean(),
                    ])
                    ->columns(3),
                Section::make('AI Assessment')
                    ->schema([
                        TextEntry::make('ai_verdict')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'SAFE' => 'success',
                                'CAUTION' => 'warning',
                                'DO_NOT_BUY' => 'danger',
                                'INCOMPLETE' => 'gray',
                                default => 'gray',
                            })
                            ->placeholder('-'),
                        TextEntry::make('risk_score')
                            ->label('Risk Score')
                            ->placeholder('-'),
                        TextEntry::make('ai_reasons')
                            ->label('AI Reasons')
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->wrap(),
                        TextEntry::make('ai_recommendation')
                            ->label('AI Recommendation')
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->wrap(),
                        TextEntry::make('ai_payload')
                            ->label('AI Payload')
                            ->formatStateUsing(fn ($state): ?string => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : null)
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->wrap()
                            ->copyable(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Submitted GPS')
                    ->schema([
                        TextEntry::make('submitted_latitude')
                            ->placeholder('-'),
                        TextEntry::make('submitted_longitude')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Issued certificate')
                    ->schema([
                        TextEntry::make('certificate.certificate_number')
                            ->label('Certificate number')
                            ->placeholder('Not issued')
                            ->url(fn (VerificationLog $record): ?string => $record->certificate
                                ? VerificationCertificateResource::getUrl('view', ['record' => $record->certificate])
                                : null),
                        TextEntry::make('certificate.certificate_type')
                            ->label('Certificate type')
                            ->formatStateUsing(fn (?string $state): string => app(AdminCertificateAssistService::class)->typeLabel($state))
                            ->placeholder('—'),
                        TextEntry::make('certificate.issued_at')
                            ->label('Issued at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('certificate_eligibility')
                            ->label('Eligibility')
                            ->state(function (VerificationLog $record): string {
                                if ($record->certificate) {
                                    return 'Certificate already issued';
                                }

                                $check = app(AdminCertificateAssistService::class)->eligibilityForLog($record);

                                return $check['eligible']
                                    ? 'Eligible — use Issue certificate action'
                                    : 'Not eligible: '.($check['reason'] ?? 'unknown');
                            })
                            ->columnSpanFull()
                            ->color(fn (VerificationLog $record): string => $record->certificate || app(AdminCertificateAssistService::class)->eligibilityForLog($record)['eligible']
                                ? 'success'
                                : 'danger'),
                    ])
                    ->columns(2),
                Section::make('Admin notes')
                    ->schema([
                        TextEntry::make('admin_notes')
                            ->placeholder('No internal notes yet.')
                            ->columnSpanFull()
                            ->wrap(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('plot.plot_reference')
                    ->label('Plot Ref')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Completed',
                        'danger' => 'Failed',
                        'warning' => 'Incomplete',
                    ])
                    ->sortable(),
                BadgeColumn::make('ai_verdict')
                    ->label('AI Verdict')
                    ->colors([
                        'success' => 'SAFE',
                        'warning' => 'CAUTION',
                        'danger' => 'DO_NOT_BUY',
                        'gray' => 'INCOMPLETE',
                    ])
                    ->sortable(),
                TextColumn::make('risk_score')
                    ->label('Risk')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('geolocation_passed')
                    ->label('Geo')
                    ->boolean(),
                IconColumn::make('nida_passed')
                    ->label('NIDA')
                    ->boolean(),
                IconColumn::make('certificate_passed')
                    ->label('Cert')
                    ->boolean(),
                TextColumn::make('certificate.certificate_number')
                    ->label('Certificate')
                    ->placeholder('Not issued')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Completed' => 'Completed',
                        'Failed' => 'Failed',
                        'Incomplete' => 'Incomplete',
                    ]),
                SelectFilter::make('ai_verdict')
                    ->options([
                        'SAFE' => 'SAFE',
                        'CAUTION' => 'CAUTION',
                        'DO_NOT_BUY' => 'DO_NOT_BUY',
                        'INCOMPLETE' => 'INCOMPLETE',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationLogs::route('/'),
            'view' => ViewVerificationLog::route('/{record}'),
        ];
    }
}
