<?php

declare(strict_types=1);
use App\Http\Controllers\Api\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key'])->prefix('v1')->group(function () {
    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files', [FileController::class, 'store']);
    Route::get('/files/{file}', [FileController::class, 'show']);
    Route::delete('/files/{file}', [FileController::class, 'destroy']);
    Route::get('/files/{file}/download', [FileController::class, 'download']);
});
