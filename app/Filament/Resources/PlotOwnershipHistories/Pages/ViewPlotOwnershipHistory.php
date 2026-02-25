<?php

namespace App\Filament\Resources\PlotOwnershipHistories\Pages;

use App\Filament\Resources\PlotOwnershipHistories\PlotOwnershipHistoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlotOwnershipHistory extends ViewRecord
{
    protected static string $resource = PlotOwnershipHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
