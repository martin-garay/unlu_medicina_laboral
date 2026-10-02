<?php

use App\Http\Controllers\CertificateDownloadController;
use App\Http\Controllers\InternalChatConsoleController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/certificados/{anticipo}/archivos/{archivo}/download', CertificateDownloadController::class)
    ->middleware('auth')->name('certificates.download');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/internal/chat', [InternalChatConsoleController::class, 'index'])
    ->name('internal-chat.console');
Route::post('/internal/chat', [InternalChatConsoleController::class, 'send'])
    ->name('internal-chat.console.send');
Route::post('/internal/chat/reset', [InternalChatConsoleController::class, 'reset'])
    ->name('internal-chat.console.reset');
