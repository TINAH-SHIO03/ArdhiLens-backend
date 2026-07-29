<?php

namespace App\Http\Controllers;

use App\Services\CertificateService;
use Illuminate\View\View;

class PublicCertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificateService,
    ) {}

    public function show(string $certificateNumber): View
    {
        $result = $this->certificateService->verifyCertificate($certificateNumber);

        return view('certificates.public_verify', [
            'certificateNumber' => $certificateNumber,
            'result' => $result,
        ]);
    }
}
