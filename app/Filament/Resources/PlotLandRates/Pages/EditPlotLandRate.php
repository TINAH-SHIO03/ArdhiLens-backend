<?php

namespace App\Filament\Resources\PlotLandRates\Pages;

use App\Filament\Resources\PlotLandRates\PlotLandRateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlotLandRate extends EditRecord
{
    protected static string $resource = PlotLandRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
