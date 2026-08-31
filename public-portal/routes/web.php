<?php

use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PublicCalendarController;
use App\Http\Controllers\SessionDetailController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\EnrollmentController;
use App\Http\Controllers\Admin\SessionDetailController as AdminSessionDetailController;
use App\Http\Controllers\Admin\RosterController;
use App\Http\Controllers\Admin\StaffAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site -- Public Calendar (Section 4e), session detail (Section 4d),
| and the "New Client Account" inquiry form (Section 3c). No auth.
|--------------------------------------------------------------------------
*/
Route::get('/calendar', [PublicCalendarController::class, 'calendar'])->name('public.calendar');
Route::get('/training-map', [PublicCalendarController::class, 'map'])->name('public.map');
Route::get('/smoke-schools', [PublicCalendarController::class, 'list'])->name('public.list');
Route::get('/smoke-schools/{state}/{slug}', [SessionDetailController::class, 'show'])->name('public.session-detail');

Route::get('/become-a-client', [InquiryController::class, 'create'])->name('public.become-a-client');
Route::post('/become-a-client', [InquiryController::class, 'store'])->name('public.become-a-client.store');

/*
|--------------------------------------------------------------------------
| Client Portal -- authenticated, client-only (Section 4e). Staff do not
| use this portal for their own work.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::post('/authorized-clients', [EnrollmentController::class, 'authorizeOutsideClient'])
        ->name('authorized-clients.store');
});

/*
|--------------------------------------------------------------------------
| Admin -- staff-facing Session Details (Section 4c) and Roster (Section
| 4f/4g). Backed by the 'staff' guard / StaffApiUserProvider -- credentials
| verified live against the Java API, no local staff password table.
|--------------------------------------------------------------------------
*/
Route::get('/admin/login', [StaffAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [StaffAuthController::class, 'login'])->name('admin.login.attempt');

Route::middleware(['auth:staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [StaffAuthController::class, 'logout'])->name('logout');

    Route::get('/sessions/{session}', [AdminSessionDetailController::class, 'show'])->name('sessions.show');
    Route::post('/sessions/{session}/comments', [AdminSessionDetailController::class, 'storeComment'])->name('sessions.comments.store');
    Route::patch('/sessions/{session}/publish', [AdminSessionDetailController::class, 'publish'])->name('sessions.publish');
    Route::post('/sessions/{session}/copy-forward', [AdminSessionDetailController::class, 'copyForward'])->name('sessions.copy-forward');

    Route::get('/sessions/{session}/roster', [RosterController::class, 'show'])->name('sessions.roster');
    Route::post('/sessions/{session}/send-summary-email', [RosterController::class, 'sendSummaryEmail'])->name('sessions.send-summary-email');
    Route::patch('/enrollments/{enrollment}/roster-status', [RosterController::class, 'updateStatus'])->name('roster.update-status');
});
