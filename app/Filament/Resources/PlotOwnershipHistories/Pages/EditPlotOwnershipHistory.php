<?php

namespace App\Filament\Resources\PlotOwnershipHistories\Pages;

use App\Filament\Resources\PlotOwnershipHistories\PlotOwnershipHistoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlotOwnershipHistory extends EditRecord
{
    protected static string $resource = PlotOwnershipHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
