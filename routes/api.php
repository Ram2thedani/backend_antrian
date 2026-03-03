<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AntrianController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LayananController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/layanans', [LayananController::class, 'index']);
Route::post('/antrians', [AntrianController::class, 'store']);
Route::get('/antrians/now-serving/{layanan}', [AntrianController::class, 'nowServing']);
Route::get('/display/now-serving', [AntrianController::class, 'displayNowServing']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/antrians/call-next', [AntrianController::class, 'callNext']);
    Route::post('/antrians/{antrian}/skip', [AntrianController::class, 'skip']);
    Route::post('/antrians/{antrian}/finish', [AntrianController::class, 'finish']);
    Route::get('/antrians/waiting/{layanan}', [AntrianController::class, 'waitingList']);
});

Route::post('/login', [AuthController::class, 'login']);
