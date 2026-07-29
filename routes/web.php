<?php

use App\Http\Controllers\PublicCertificateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/verify/{certificateNumber}', [PublicCertificateController::class, 'show'])
    ->where('certificateNumber', '[A-Za-z0-9\-]+');
