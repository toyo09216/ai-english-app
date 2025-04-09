<?php

use App\Http\Controllers\ThreadController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/top', [ThreadController::class, 'index'])
        ->name('top');
    Route::get('/thread/{threadId}', [ThreadController::class, 'show'])
        ->name('thread.show');
    Route::post('/thread', [ThreadController::class, 'store'])
        ->name('thread.store');
    Route::post('/thread/{threadId}/message', [MessageController::class, 'store'])
        ->name('message.store');
    // メッセージを日本語に翻訳
    Route::post('/thread/{threadId}/message/{messageId}/translate', [MessageController::class, 'translate'])
        ->name('message.translate')->where('threadId', '[0-9]+')->where('messageId', '[0-9]+');
});
