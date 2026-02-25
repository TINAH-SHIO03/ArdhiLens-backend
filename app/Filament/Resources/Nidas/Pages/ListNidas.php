<?php

namespace App\Filament\Resources\Nidas\Pages;

use App\Filament\Resources\Nidas\NidaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNidas extends ListRecords
{
    protected static string $resource = NidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
