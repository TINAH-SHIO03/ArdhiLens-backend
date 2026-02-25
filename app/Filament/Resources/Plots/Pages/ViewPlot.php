<?php

namespace App\Filament\Resources\Plots\Pages;

use App\Filament\Resources\Plots\PlotResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlot extends ViewRecord
{
    protected static string $resource = PlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
