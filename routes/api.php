<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LandVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('land-verification')->middleware('auth:sanctum')->group(function () {
    Route::post('/plot', [LandVerificationController::class, 'findPlot']);
    Route::post('/gps', [LandVerificationController::class, 'verifyGps']);
    Route::post('/nin/questions', [LandVerificationController::class, 'generateNinQuestions']);
    Route::post('/nin/answers', [LandVerificationController::class, 'verifyNinAnswers']);
    Route::post('/assistant/explain', [LandVerificationController::class, 'explainAssessment']);
});
