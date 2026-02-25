<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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

                Section::make('Personal & Verification Details')
                    ->description('Linked NIDA and contact info')
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->schema([
                        TextInput::make('nin')
                            ->label('NIN')
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->disabled(),
                        TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(255),
                        DateTimePicker::make('verified_at')
                            ->label('NIDA Verified At')
                            ->disabled() // Optional: admins set via action, not manually
                            ->placeholder('Auto-set on verification'),
                    ])
                    ->columns(2),

                Section::make('Role & Status')
                    ->description('Access level and account state')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'buyer' => 'Buyer',
                                'seller' => 'Seller',
                            ])
                            ->required()
                            ->default('buyer'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}