<?php

use App\Http\Controllers\Api\ApiFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key'])->prefix('v1')->group(function () {
    Route::get('/files', [ApiFileController::class, 'index']);
    Route::post('/files', [ApiFileController::class, 'store']);
    Route::get('/files/{file}', [ApiFileController::class, 'show']);
    Route::delete('/files/{file}', [ApiFileController::class, 'destroy']);
    Route::get('/files/{file}/download', [ApiFileController::class, 'download']);
});
