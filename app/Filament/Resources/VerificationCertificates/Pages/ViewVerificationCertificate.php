<?php

namespace App\Filament\Resources\VerificationCertificates\Pages;

use App\Filament\Resources\VerificationCertificates\VerificationCertificateResource;
use App\Filament\Support\CertificateAssistActions;
use Filament\Resources\Pages\ViewRecord;

class ViewVerificationCertificate extends ViewRecord
{
    protected static string $resource = VerificationCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return CertificateAssistActions::forCertificate($this->record);
    }
}
