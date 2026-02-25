<?php

namespace App\Filament\Resources\Plots\Schemas;

use App\Models\Nida;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

class PlotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Owner Verification')
                        ->description('Verify owner NIDA first before entering other plot details.')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            TextInput::make('plot_reference')
                                ->required()
                                ->maxLength(50)
                                ->unique(ignoreRecord: true)
                                ->helperText('Unique plot reference used across the system.'),
                            Select::make('owner_nida')
                                ->label('Owner NIDA (NIN)')
                                ->required()
                                ->relationship(
                                    name: 'owner',
                                    titleAttribute: 'nin',
                                    modifyQueryUsing: fn (Builder $query): Builder => $query->where('status', 'Active'),
                                )
                                ->searchable(['nin', 'first_name', 'middle_name', 'surname'])
                                ->getOptionLabelFromRecordUsing(fn (Nida $record): string => "{$record->nin} - {$record->full_name}")
                                ->rules([
                                    Rule::exists('nidas', 'nin')
                                        ->where(fn (QueryBuilder $query): QueryBuilder => $query
                                            ->where('status', 'Active')
                                            ->whereNull('deleted_at')),
                                ])
                                ->helperText('Search by NIN or name. Only active NIDA records are selectable.'),
                        ])
                        ->columns(2),

                    Step::make('Location')
                        ->description('Capture the exact administrative and map location.')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            TextInput::make('region')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('district')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('ward')
                                ->required()
                                ->maxLength(100),
                            TextInput::make('village_mtaa')
                                ->required()
                                ->maxLength(150),
                            TextInput::make('street')
                                ->maxLength(100),
                            TextInput::make('gps_latitude')
                                ->numeric()
                                ->label('GPS Latitude'),
                            TextInput::make('gps_longitude')
                                ->numeric()
                                ->label('GPS Longitude'),
                        ])
                        ->columns(2),

                    Step::make('Land Details')
                        ->description('Set land, certificate, and compliance details.')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            TextInput::make('size_hectares')
                                ->required()
                                ->numeric()
                                ->minValue(0.0001),
                            Select::make('land_use')
                                ->required()
                                ->options([
                                    'Residential' => 'Residential',
                                    'Commercial' => 'Commercial',
                                    'Agricultural' => 'Agricultural',
                                    'Industrial' => 'Industrial',
                                    'Mixed' => 'Mixed',
                                ]),
                            Select::make('tenure_type')
                                ->required()
                                ->options([
                                    'Granted' => 'Granted',
                                    'Customary' => 'Customary',
                                    'Leasehold' => 'Leasehold',
                                ]),
                            Select::make('certificate_type')
                                ->required()
                                ->options([
                                    'Title' => 'Title',
                                    'CCRO' => 'CCRO',
                                    'Letter of Offer' => 'Letter of Offer',
                                ]),
                            DatePicker::make('issue_date')
                                ->required(),
                            DatePicker::make('expiry_date')
                                ->rules(['nullable', 'date', 'after_or_equal:issue_date']),
                            Select::make('status')
                                ->required()
                                ->default('Active')
                                ->options([
                                    'Active' => 'Active',
                                    'Revoked' => 'Revoked',
                                    'Under Review' => 'Under Review',
                                    'Disputed' => 'Disputed',
                                ]),
                            Toggle::make('zoning_compliant')
                                ->default(true),
                            Toggle::make('development_conditions_met')
                                ->default(true),
                            Toggle::make('double_allocation_flag')
                                ->default(false),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
            ]);
    }
}
