<?php

namespace App\Filament\Resources\PlotCaveats\Pages;

use App\Filament\Resources\PlotCaveats\PlotCaveatResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlotCaveat extends ViewRecord
{
    protected static string $resource = PlotCaveatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
