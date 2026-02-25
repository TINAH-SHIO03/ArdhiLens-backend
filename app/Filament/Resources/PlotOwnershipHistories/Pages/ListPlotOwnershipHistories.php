<?php

namespace App\Filament\Resources\PlotOwnershipHistories\Pages;

use App\Filament\Resources\PlotOwnershipHistories\PlotOwnershipHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlotOwnershipHistories extends ListRecords
{
    protected static string $resource = PlotOwnershipHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
