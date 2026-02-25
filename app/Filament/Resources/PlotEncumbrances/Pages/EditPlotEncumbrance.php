<?php

namespace App\Filament\Resources\PlotEncumbrances\Pages;

use App\Filament\Resources\PlotEncumbrances\PlotEncumbranceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPlotEncumbrance extends EditRecord
{
    protected static string $resource = PlotEncumbranceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
