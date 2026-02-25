<?php

namespace App\Filament\Resources\PlotDisputes\Pages;

use App\Filament\Resources\PlotDisputes\PlotDisputeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlotDispute extends EditRecord
{
    protected static string $resource = PlotDisputeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
