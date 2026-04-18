<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;

Route::post('/login', [UserController::class, 'login']);
Route::post('/users',[UserController::class, 'store']);

Route::middleware('auth:sanctum')->group(function(){
    Route::apiResource('users', UserController::class)->except(['store']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/stats',[TransactionController::class, 'stats']);
    Route::get('/transactions/export',[TransactionController::class, 'export']);
});

