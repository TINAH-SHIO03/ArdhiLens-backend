<?php

namespace App\Filament\Resources\Plots\Pages;

use App\Filament\Resources\Plots\PlotResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlots extends ListRecords
{
    protected static string $resource = PlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
