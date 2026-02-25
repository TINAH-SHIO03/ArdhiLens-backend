<?php

namespace App\Filament\Resources\PlotLandRates\Pages;

use App\Filament\Resources\PlotLandRates\PlotLandRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlotLandRates extends ListRecords
{
    protected static string $resource = PlotLandRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
