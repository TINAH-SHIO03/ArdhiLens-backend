<?php

namespace App\Filament\Resources\VerificationCertificates\Pages;

use App\Filament\Resources\VerificationCertificates\VerificationCertificateResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVerificationCertificates extends ListRecords
{
    protected static string $resource = VerificationCertificateResource::class;

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()?->with('user');
    }
}
