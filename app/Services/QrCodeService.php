<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * Generate a PNG QR code and return as base64 data URI for DomPDF.
     */
    public function toDataUri(string $payload, int $size = 220): string
    {
        $qrCode = QrCode::create($payload)
            ->setSize($size)
            ->setMargin(8);

        $result = (new PngWriter)->write($qrCode);

        return $result->getDataUri();
    }
}
