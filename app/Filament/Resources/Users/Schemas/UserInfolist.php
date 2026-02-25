<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;  // Optional: if you need it for closures
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;  // ← Correct import for v4
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->description('Basic account information')  // Optional: adds helpful text
                    ->icon('heroicon-o-user-circle')            // Optional: nice icon
                    ->collapsible()                             // Optional: users can collapse
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('role')
                            ->label('Role')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'admin'  => 'danger',
                                'buyer'  => 'warning',
                                'seller' => 'success',
                                default  => 'gray',
                            }),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('NIDA & Verification')
                    ->description('Linked NIDA and verification status')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('nin')
                            ->label('NIN'),
                        TextEntry::make('phone_number')
                            ->label('Phone Number'),
                        TextEntry::make('verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->placeholder('-')
                            ->formatStateUsing(fn ($state) => $state ? $state : '-'),
                    ])
                    ->columns(2),

                // Optional: Add more sections here (e.g., for verification logs relation)
                // Section::make('Verification History')
                //     ->schema([...])
            ]);
    }
}