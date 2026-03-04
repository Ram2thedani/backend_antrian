<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AntrianController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LayananController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/layanan', [LayananController::class, 'index']);
Route::post('/antrian', [AntrianController::class, 'store']);
Route::get('/antrian/now-serving/{layanan}', [AntrianController::class, 'nowServing']);
Route::get('/display/now-serving', [AntrianController::class, 'displayNowServing']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/antrian/call-next', [AntrianController::class, 'callNext']);
    Route::post('/antrian/{antrian}/skip', [AntrianController::class, 'skip']);
    Route::post('/antrian/{antrian}/finish', [AntrianController::class, 'finish']);
    Route::get('/antrian/waiting/{layanan}', [AntrianController::class, 'waitingList']);
});

Route::post('/login', [AuthController::class, 'login']);
