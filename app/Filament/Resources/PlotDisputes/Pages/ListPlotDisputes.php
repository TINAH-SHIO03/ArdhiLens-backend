<?php

namespace App\Filament\Resources\PlotDisputes\Pages;

use App\Filament\Resources\PlotDisputes\PlotDisputeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlotDisputes extends ListRecords
{
    protected static string $resource = PlotDisputeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
