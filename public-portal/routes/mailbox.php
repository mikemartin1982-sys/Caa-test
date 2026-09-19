<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailboxMessageController;
use App\Http\Controllers\MailboxMessageTemplateController;

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
