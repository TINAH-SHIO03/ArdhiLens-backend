<?php

namespace App\Filament\Resources\Nidas\Pages;

use App\Filament\Resources\Nidas\NidaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNida extends ViewRecord
{
    protected static string $resource = NidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
