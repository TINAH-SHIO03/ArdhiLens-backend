<?php

namespace App\Filament\Resources\PlotEncumbrances\Pages;

use App\Filament\Resources\PlotEncumbrances\PlotEncumbranceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlotEncumbrance extends ViewRecord
{
    protected static string $resource = PlotEncumbranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
