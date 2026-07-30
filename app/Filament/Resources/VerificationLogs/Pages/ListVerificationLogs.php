<?php

namespace App\Filament\Resources\VerificationLogs\Pages;

use App\Filament\Resources\VerificationLogs\VerificationLogResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVerificationLogs extends ListRecords
{
    protected static string $resource = VerificationLogResource::class;

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()?->with(['user', 'plot', 'certificate']);
    }
}
