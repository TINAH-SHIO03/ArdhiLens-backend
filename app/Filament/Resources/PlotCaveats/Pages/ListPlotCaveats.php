<?php

namespace App\Filament\Resources\PlotCaveats\Pages;

use App\Filament\Resources\PlotCaveats\PlotCaveatResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlotCaveats extends ListRecords
{
    protected static string $resource = PlotCaveatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
