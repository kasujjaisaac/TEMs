<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\LegacyPageController;

Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/index.php', [AuthController::class, 'showLogin'])->name('home.legacy');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login/otp', [AuthController::class, 'showOtp'])->name('login.otp');
Route::post('/login/otp', [AuthController::class, 'verifyOtp'])->name('login.otp.verify');
Route::post('/login/otp/resend', [AuthController::class, 'resendOtp'])->name('login.otp.resend');
Route::get('/register', fn () => redirect()->route('login'));
Route::post('/register', fn () => redirect()->route('login'));
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.update');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::get('/settings/users', [AccessControlController::class, 'users'])->name('settings.users');
    Route::get('/settings/users/create', [AccessControlController::class, 'createUser'])->name('settings.users.create');
    Route::post('/settings/users', [AccessControlController::class, 'storeUser'])->name('settings.users.store');
    Route::put('/settings/users/{user}', [AccessControlController::class, 'updateUser'])->name('settings.users.update');

    Route::get('/settings/roles', [AccessControlController::class, 'roles'])->name('settings.roles');
    Route::get('/settings/roles/create', [AccessControlController::class, 'createRole'])->name('settings.roles.create');
    Route::post('/settings/roles', [AccessControlController::class, 'storeRole'])->name('settings.roles.store');
    Route::get('/settings/roles/{role}', [AccessControlController::class, 'showRole'])->name('settings.roles.show');
    Route::get('/settings/roles/{role}/edit', [AccessControlController::class, 'editRole'])->name('settings.roles.edit');
    Route::put('/settings/roles/{role}', [AccessControlController::class, 'updateRole'])->name('settings.roles.update');

    Route::get('/settings/security', [AccessControlController::class, 'security'])->name('settings.security');
    Route::put('/settings/security', [AccessControlController::class, 'updateSecurity'])->name('settings.security.update');

    Route::get('/settings/audit-logs', [AccessControlController::class, 'auditLogs'])->name('settings.audit_logs');
});

// Migrated ERP pages. The .php routes keep existing in-page links working while
// clean Laravel URLs are available for the same pages.
foreach (onyx_legacy_pages() as $page) {
    if ($page !== 'assets') {
        Route::match(['GET', 'POST'], '/' . $page, [LegacyPageController::class, 'show'])
            ->middleware('password.changed')
            ->defaults('page', $page)
            ->name('erp.' . $page);
    }

    Route::match(['GET', 'POST'], '/' . $page . '.php', [LegacyPageController::class, 'show'])
        ->middleware('password.changed')
        ->defaults('page', $page)
        ->name('erp.' . $page . '.legacy');
}
