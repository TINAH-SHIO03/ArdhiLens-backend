<?php

namespace App\Filament\Resources\PlotCaveats\Pages;

use App\Filament\Resources\PlotCaveats\PlotCaveatResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlotCaveat extends EditRecord
{
    protected static string $resource = PlotCaveatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
