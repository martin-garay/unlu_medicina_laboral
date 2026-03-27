<?php

use App\Http\Controllers\InternalChatConsoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/internal/chat', [InternalChatConsoleController::class, 'index'])
    ->name('internal-chat.console');
Route::post('/internal/chat', [InternalChatConsoleController::class, 'send'])
    ->name('internal-chat.console.send');
Route::post('/internal/chat/reset', [InternalChatConsoleController::class, 'reset'])
    ->name('internal-chat.console.reset');
