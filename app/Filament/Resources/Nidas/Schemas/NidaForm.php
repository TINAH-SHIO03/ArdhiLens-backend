<?php

namespace App\Filament\Resources\Nidas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NidaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Basic Personal Details
                Section::make('Basic Personal Information')
                    ->description('Core identity details')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('nin')
                            ->label('National Identification Number (NIN)')
                            ->required()
                            ->maxLength(20)
                           ->unique(ignoreRecord: true),
                        TextInput::make('first_name')
                            ->required(),
                        TextInput::make('middle_name'),
                        TextInput::make('surname')
                            ->required(),
                        Select::make('gender')
                            ->options(['M' => 'Male', 'F' => 'Female'])
                            ->required(),
                        DatePicker::make('date_of_birth')
                            ->required()
                            ->maxDate(now()),
                    ])
                    ->columns(2)
                    ->collapsible(),

              

                // Marital & Professional Information
                Section::make('Marital & Professional Details')
                    ->description('Personal status and occupation')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Select::make('marital_status')
                            ->options([
                                'Single' => 'Single',
                                'Married' => 'Married',
                                'Widowed' => 'Widowed',
                                'Divorced' => 'Divorced',
                            ]),
                        TextInput::make('occupation')
                            ->label('Occupation'),
                        TextInput::make('highest_education')
                            ->label('Highest Education Level'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Father's Information
                Section::make("Father's Information")
                    ->description("Details about the applicant's father")
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        TextInput::make('father_first_name'),
                        TextInput::make('father_middle_name'),
                        TextInput::make('father_surname'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // Mother's Information
                Section::make("Mother's Information")
                    ->description("Details about the applicant's mother")
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        TextInput::make('mother_first_name'),
                        TextInput::make('mother_middle_name'),
                        TextInput::make('mother_surname'),
                    ])
                    ->columns(3)
                    ->collapsible(),
             
                // Residence Address
                Section::make('Current Residence Address')
                    ->description('Where the applicant currently lives')
                    ->icon('heroicon-o-home')
                    ->schema([
                        TextInput::make('res_region')
                            ->label('Region'),
                        TextInput::make('res_district')
                            ->label('District'),
                        TextInput::make('res_ward')
                            ->label('Ward'),
                        TextInput::make('res_mtaa')
                            ->label('Mtaa/Street/Village'),
                        TextInput::make('res_postcode')
                            ->label('Postcode'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // Permanent Address
                Section::make('Permanent Address')
                    ->description('Permanent/home address')
                    ->icon('heroicon-o-home-modern')
                    ->schema([
                        TextInput::make('perm_region')
                            ->label('Region'),
                        TextInput::make('perm_district')
                            ->label('District'),
                        TextInput::make('perm_ward')
                            ->label('Ward'),
                        TextInput::make('perm_mtaa')
                            ->label('Mtaa/Street/Village'),
                    ])
                    ->columns(2)
                    ->collapsible(),


                         // Identification Documents
                Section::make('Identification Documents')
                    ->description('Official document numbers')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Select::make('nationality')
                            ->options([
                                'Tanzanian' => 'Tanzanian',
                                'Resident' => 'Resident',
                                'Refugee' => 'Refugee',
                            ])
                            ->default('Tanzanian')
                            ->required(),
                        TextInput::make('birth_certificate_number')
                            ->label('Birth Certificate Number'),
                        TextInput::make('passport_number')
                            ->label('Passport Number'),
                        FileUpload::make('passport_image_path')
                            ->label('Passport Image')
                            ->image()
                            ->disk('public')
                            ->directory('nida/passports')
                            ->visibility('public')
                            ->helperText('Upload image file (stored as path, not base64).')
                            ->columnSpanFull(),
                        TextInput::make('voter_id')
                            ->label('Voter ID'),
                    ])
                    ->columns(2)
                    ->collapsible(),

              

                // Status & Issuance
                Section::make('Administrative Status')
                    ->description('Record status and issuance details')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'Active' => 'Active',
                                'Suspended' => 'Suspended',
                                'Deceased' => 'Deceased',
                                'Pending' => 'Pending',
                            ])
                            ->default('Active')
                            ->required(),
                        DateTimePicker::make('issued_at')
                            ->label('Issued At'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                     // Contact & Media
                Section::make('Contact & Identification Media')
                    ->description('Phone and visual identifiers')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('phone_number')
                            ->tel()
                            ->label('Phone Number'),
                        Textarea::make('photo_base64')
                            ->label('Photo (Base64)')
                            ->rows(4)
                            ->columnSpanFull(),
                        Textarea::make('signature_base64')
                            ->label('Signature (Base64)')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
