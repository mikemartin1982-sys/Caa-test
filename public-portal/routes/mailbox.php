<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailboxMessageController;
use App\Http\Controllers\MailboxMessageTemplateController;

// Connection setup is available while mailbox importing/sending is disabled.
Route::middleware(['auth:staff'])->prefix('admin/mailbox/microsoft')->name('admin.mailbox.')->group(function () {
    Route::get('/', [\App\Http\Controllers\GraphAuthController::class, 'show'])->name('connection');
    Route::post('/connect', [\App\Http\Controllers\GraphAuthController::class, 'connect'])->middleware('throttle:10,1')->name('connect');
    Route::get('/callback', [\App\Http\Controllers\GraphAuthController::class, 'callback'])->name('callback');
});

Route::middleware(['auth:staff', \App\Http\Middleware\EnsureMailboxEnabled::class])->prefix('admin/mailbox')->name('admin.mailbox.')->group(function () {
    Route::get('/', [MailboxMessageController::class, 'index'])->name('index');
    Route::get('/templates', [MailboxMessageTemplateController::class, 'index'])->name('templates.index');
    Route::post('/templates', [MailboxMessageTemplateController::class, 'store'])->name('templates.store');
    Route::put('/templates/{template}', [MailboxMessageTemplateController::class, 'update'])->name('templates.update');
    Route::get('/{message}', [MailboxMessageController::class, 'show'])->name('show');
    Route::post('/{message}/reply', [MailboxMessageController::class, 'reply'])->name('reply');
    Route::patch('/{message}/status', [MailboxMessageController::class, 'updateStatus'])->name('status');
    Route::get('/{message}/templates/{template}/preview', [MailboxMessageController::class, 'previewTemplate'])->name('templates.preview');
});
