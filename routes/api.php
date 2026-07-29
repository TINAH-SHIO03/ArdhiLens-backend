<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\LandVerificationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PurchaseInterestController;
use App\Http\Controllers\Api\SellerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:auth')->prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::prefix('auth')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
    Route::get('/avatar', [AuthController::class, 'avatar']);
    Route::post('/email/send-code', [AuthController::class, 'sendEmailVerification']);
    Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('land-verification')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/plot', [LandVerificationController::class, 'findPlot']);
    Route::post('/gps', [LandVerificationController::class, 'verifyGps']);
    Route::post('/nin/questions', [LandVerificationController::class, 'generateNinQuestions']);
    Route::post('/nin/answers', [LandVerificationController::class, 'verifyNinAnswers']);
    Route::post('/assistant/explain', [LandVerificationController::class, 'explainAssessment']);
});

Route::prefix('seller')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/dashboard', [SellerController::class, 'dashboard']);
    Route::get('/recent-verifications', [SellerController::class, 'recentVerifications']);
    Route::post('/kyc', [SellerController::class, 'submitKyc']);
    Route::get('/interests', [PurchaseInterestController::class, 'forSeller']);
    Route::put('/interests/{id}/respond', [PurchaseInterestController::class, 'respond']);
});

Route::prefix('buyer')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/interests', [PurchaseInterestController::class, 'mine']);
    Route::post('/interests', [PurchaseInterestController::class, 'store']);
});

Route::prefix('notifications')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    Route::post('/device-token', [NotificationController::class, 'registerDeviceToken']);
    Route::delete('/device-token', [NotificationController::class, 'removeDeviceToken']);
});

Route::prefix('certificates')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('/generate', [CertificateController::class, 'generate']);
    Route::get('/', [CertificateController::class, 'list']);
    Route::get('/{id}/download', [CertificateController::class, 'download']);
});

Route::get('/certificates/verify/{certificateNumber}', [CertificateController::class, 'verify'])
    ->middleware('throttle:api');

Route::prefix('documents')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/', [DocumentController::class, 'index']);
    Route::post('/upload', [DocumentController::class, 'upload']);
    Route::get('/{id}/download', [DocumentController::class, 'download']);
    Route::delete('/{id}', [DocumentController::class, 'destroy']);
    Route::post('/{id}/review', [DocumentController::class, 'review']);
});
