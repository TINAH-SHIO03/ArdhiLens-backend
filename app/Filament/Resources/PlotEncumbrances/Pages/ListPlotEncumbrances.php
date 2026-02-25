<?php

namespace App\Filament\Resources\PlotEncumbrances\Pages;

use App\Filament\Resources\PlotEncumbrances\PlotEncumbranceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlotEncumbrances extends ListRecords
{
    protected static string $resource = PlotEncumbranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
