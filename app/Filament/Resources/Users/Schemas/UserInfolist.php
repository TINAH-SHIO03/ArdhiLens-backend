<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Details')
                    ->description('Basic account information')
                    ->icon('heroicon-o-user-circle')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('role')
                            ->label('Role')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'admin' => 'danger',
                                'buyer' => 'warning',
                                'seller' => 'success',
                                default => 'gray',
                            }),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('NIDA & Plot Link')
                    ->description('NIN links this user to plots where owner_nida matches')
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('nin')
                            ->label('NIN')
                            ->placeholder('—'),
                        TextEntry::make('phone_number')
                            ->label('Phone Number')
                            ->placeholder('—'),
                        TextEntry::make('verified_at')
                            ->label('Verified At')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('linked_plots_count')
                            ->label('Linked plots')
                            ->state(fn ($record) => $record->nin
                                ? $record->linkedPlots()->count()
                                : 0),
                    ])
                    ->columns(2),

                Section::make('Seller KYC')
                    ->description('Identity verification status for sellers')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('kyc_status')
                            ->label('KYC Status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'verified' => 'success',
                                'pending_review', 'needs_manual_review', 'required' => 'warning',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('face_match_score')
                            ->label('Face match score')
                            ->placeholder('—'),
                        IconEntry::make('face_match_passed')
                            ->label('Face match passed')
                            ->boolean(),
                        TextEntry::make('kyc_submitted_at')
                            ->label('Submitted at')
                            ->dateTime()
                            ->placeholder('—'),
                        TextEntry::make('kyc_notes')
                            ->label('Notes')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }
}
