<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SmsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('send-otp', [AuthController::class, 'sendOtp']);
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        Route::patch('change-password', [AuthController::class, 'updatePassword']);
    });
    Route::prefix('sms')->middleware('auth:sanctum')->group(function () {
        Route::post('/send', [SmsController::class, 'sendSms']);
        Route::post('/schedule', [SmsController::class, 'schedule']);
        Route::get('/', [SmsController::class, 'getSmsHistory']);
        Route::get('/{id}', [SmsController::class, 'getSmsHistoryDetails']);
    });
});
