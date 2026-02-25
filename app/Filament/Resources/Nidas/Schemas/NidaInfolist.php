<?php

namespace App\Filament\Resources\Nidas\Schemas;

use App\Models\Nida;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NidaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Basic Personal Information
                Section::make('Basic Personal Information')
                    ->description('Core identity details')
                    ->icon('heroicon-o-user')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('nin')
                            ->label('National Identification Number (NIN)'),
                        TextEntry::make('full_name')
                            ->label('Full Name')
                            ->getStateUsing(fn (Nida $record): string => $record->full_name),
                        TextEntry::make('gender')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'M' => 'primary',
                                'F' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('date_of_birth')
                            ->date()
                            ->label('Date of Birth'),
                        TextEntry::make('nationality')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Tanzanian' => 'success',
                                'Resident' => 'warning',
                                'Refugee' => 'danger',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                // Identification Documents
                Section::make('Identification Documents')
                    ->description('Official document references')
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('birth_certificate_number')
                            ->placeholder('-'),
                        TextEntry::make('passport_number')
                            ->placeholder('-'),
                        ImageEntry::make('passport_image_path')
                            ->label('Passport Image')
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(140)
                            ->square()
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('voter_id')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                // Marital & Professional Details
                Section::make('Marital & Professional Details')
                    ->description('Personal and career status')
                    ->icon('heroicon-o-briefcase')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('marital_status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Married' => 'success',
                                'Single' => 'gray',
                                'Divorced' => 'danger',
                                'Widowed' => 'warning',
                                default => 'gray',
                            })
                            ->placeholder('-'),
                        TextEntry::make('occupation')
                            ->placeholder('-'),
                        TextEntry::make('highest_education')
                            ->label('Highest Education')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                // Family Information
                Section::make("Parents' Information")
                    ->description("Details about the applicant's parents")
                    ->icon('heroicon-o-user-group')
                    ->collapsible()
                    ->schema([
                        Section::make("Father's Details")
                            ->compact()
                            ->schema([
                                TextEntry::make('father_first_name')
                                    ->placeholder('-'),
                                TextEntry::make('father_middle_name')
                                    ->placeholder('-'),
                                TextEntry::make('father_surname')
                                    ->placeholder('-'),
                            ])
                            ->columns(3),

                        Section::make("Mother's Details")
                            ->compact()
                            ->schema([
                                TextEntry::make('mother_first_name')
                                    ->placeholder('-'),
                                TextEntry::make('mother_middle_name')
                                    ->placeholder('-'),
                                TextEntry::make('mother_surname')
                                    ->placeholder('-'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(1),

                // Residence Address
                Section::make('Current Residence Address')
                    ->description('Where the applicant currently resides')
                    ->icon('heroicon-o-home')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('res_region')
                            ->label('Region')
                            ->placeholder('-'),
                        TextEntry::make('res_district')
                            ->label('District')
                            ->placeholder('-'),
                        TextEntry::make('res_ward')
                            ->label('Ward')
                            ->placeholder('-'),
                        TextEntry::make('res_mtaa')
                            ->label('Mtaa / Street / Village')
                            ->placeholder('-'),
                        TextEntry::make('res_postcode')
                            ->label('Postcode')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                // Permanent Address
                Section::make('Permanent Address')
                    ->description('Registered permanent address')
                    ->icon('heroicon-o-home-modern')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('perm_region')
                            ->label('Region')
                            ->placeholder('-'),
                        TextEntry::make('perm_district')
                            ->label('District')
                            ->placeholder('-'),
                        TextEntry::make('perm_ward')
                            ->label('Ward')
                            ->placeholder('-'),
                        TextEntry::make('perm_mtaa')
                            ->label('Mtaa / Street / Village')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                // Contact & Media
                Section::make('Contact & Identification Media')
                    ->description('Phone number and visual identifiers')
                    ->icon('heroicon-o-phone')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('phone_number')
                            ->label('Phone Number')
                            ->placeholder('-'),
                        TextEntry::make('photo_base64')
                            ->label('Photo (Base64)')
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->formatStateUsing(fn (?string $state): ?string => $state ? 'Base64 data present (long string)' : null),
                        TextEntry::make('signature_base64')
                            ->label('Signature (Base64)')
                            ->placeholder('-')
                            ->columnSpanFull()
                            ->formatStateUsing(fn (?string $state): ?string => $state ? 'Base64 data present (long string)' : null),
                    ]),

                // Administrative Status
                Section::make('Administrative Status')
                    ->description('Record status and important dates')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Active' => 'success',
                                'Suspended' => 'danger',
                                'Deceased' => 'gray',
                                'Pending' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('issued_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->placeholder('-')
                            ->visible(fn (Nida $record): bool => $record->trashed()),
                    ])
                    ->columns(2),
            ]);
    }
}
