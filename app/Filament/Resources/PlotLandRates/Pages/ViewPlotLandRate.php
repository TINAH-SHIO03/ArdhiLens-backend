<?php

namespace App\Filament\Resources\PlotLandRates\Pages;

use App\Filament\Resources\PlotLandRates\PlotLandRateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlotLandRate extends ViewRecord
{
    protected static string $resource = PlotLandRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
