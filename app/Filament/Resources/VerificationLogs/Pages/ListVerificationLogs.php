<?php

namespace App\Filament\Resources\VerificationLogs\Pages;

use App\Filament\Resources\VerificationLogs\VerificationLogResource;
use Filament\Resources\Pages\ListRecords;

class ListVerificationLogs extends ListRecords
{
    protected static string $resource = VerificationLogResource::class;
}
