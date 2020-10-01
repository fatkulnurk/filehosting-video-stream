<?php

use App\Http\Controllers\Dashboard\DirectoryController;
use App\Http\Controllers\Dashboard\FilesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::group([
    'middleware' => ['auth:sanctum', 'verified'],
    'as' => 'dashboard.',
    'prefix' => '/dashboard'
], function (){
    Route::get('/', function () {
        return view('dashboard');
    })->name('index');
    Route::resource('/files', FilesController::class);
    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index');
    Route::post('/directory', [DirectoryController::class, 'store'])->name('directory.store');
});
//
//Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
//    return view('dashboard');
//})->name('dashboard');
