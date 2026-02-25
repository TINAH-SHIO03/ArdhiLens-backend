<?php

namespace App\Filament\Resources\PlotDisputes\Pages;

use App\Filament\Resources\PlotDisputes\PlotDisputeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlotDispute extends ViewRecord
{
    protected static string $resource = PlotDisputeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
