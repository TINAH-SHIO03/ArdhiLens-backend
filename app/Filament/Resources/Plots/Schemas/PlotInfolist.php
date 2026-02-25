<?php

namespace App\Filament\Resources\Plots\Schemas;

use App\Models\Plot;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlotInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Owner & Reference')
                    ->schema([
                        TextEntry::make('plot_reference')
                            ->label('Plot Reference'),
                        TextEntry::make('owner_name')
                            ->label('Owner')
                            ->getStateUsing(fn (Plot $record): string => $record->owner
                                ? "{$record->owner->full_name} ({$record->owner_nida})"
                                : $record->owner_nida),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Active' => 'success',
                                'Revoked' => 'danger',
                                'Under Review' => 'warning',
                                'Disputed' => 'gray',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Section::make('Location')
                    ->schema([
                        TextEntry::make('region'),
                        TextEntry::make('district'),
                        TextEntry::make('ward'),
                        TextEntry::make('village_mtaa')
                            ->label('Village / Mtaa'),
                        TextEntry::make('street')
                            ->placeholder('-'),
                        TextEntry::make('gps_latitude')
                            ->placeholder('-'),
                        TextEntry::make('gps_longitude')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Land Details')
                    ->schema([
                        TextEntry::make('size_hectares')
                            ->numeric(decimalPlaces: 4),
                        TextEntry::make('land_use')
                            ->badge(),
                        TextEntry::make('tenure_type')
                            ->badge(),
                        TextEntry::make('certificate_type')
                            ->badge(),
                        TextEntry::make('issue_date')
                            ->date(),
                        TextEntry::make('expiry_date')
                            ->date()
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Compliance Flags')
                    ->schema([
                        IconEntry::make('zoning_compliant')
                            ->label('Zoning Compliant')
                            ->boolean(),
                        IconEntry::make('development_conditions_met')
                            ->label('Development Conditions Met')
                            ->boolean(),
                        IconEntry::make('double_allocation_flag')
                            ->label('Double Allocation Flag')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                        TextEntry::make('deleted_at')
                            ->dateTime()
                            ->placeholder('-')
                            ->visible(fn (Plot $record): bool => $record->trashed()),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
