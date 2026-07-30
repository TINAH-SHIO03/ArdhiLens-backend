<?php

use App\Http\Controllers\PublicCertificateController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::get('/verify/{certificateNumber}', [PublicCertificateController::class, 'show'])
    ->where('certificateNumber', '[A-Za-z0-9\-]+');
