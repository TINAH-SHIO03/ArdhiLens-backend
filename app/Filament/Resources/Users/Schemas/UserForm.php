<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->description('Login and basic details')
                    ->icon('heroicon-o-user-circle')
                    ->collapsible()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Personal & NIN Link')
                    ->description('Link seller/buyer to NIDA. Plots with matching owner_nida appear in their app.')
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->schema([
                        TextInput::make('nin')
                            ->label('NIN')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->helperText('Set this to the owner NIN so plots auto-link to this account.'),
                        TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(255),
                        DateTimePicker::make('verified_at')
                            ->label('NIDA Verified At'),
                    ])
                    ->columns(2),

                Section::make('Seller KYC')
                    ->description('Review and update seller identity verification')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Select::make('kyc_status')
                            ->label('KYC Status')
                            ->options([
                                'none' => 'None',
                                'required' => 'Required',
                                'pending_review' => 'Pending review',
                                'needs_manual_review' => 'Needs manual review',
                                'verified' => 'Verified',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->default('none'),
                        TextInput::make('face_match_score')
                            ->label('Face match score')
                            ->numeric()
                            ->disabled(),
                        Toggle::make('face_match_passed')
                            ->label('Face match passed')
                            ->disabled(),
                        DateTimePicker::make('kyc_submitted_at')
                            ->label('KYC submitted at')
                            ->disabled(),
                        Textarea::make('kyc_notes')
                            ->label('KYC notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Role & Status')
                    ->description('Access level and account state')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Select::make('role')
                            ->options([
                                'admin' => 'Admin (web only)',
                                'buyer' => 'Buyer',
                                'seller' => 'Seller',
                            ])
                            ->required()
                            ->default('buyer')
                            ->helperText('Admins sign in at /admin on the web — not in the mobile app.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
