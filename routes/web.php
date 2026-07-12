<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LegacyPageController;

Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/index.php', [AuthController::class, 'showLogin'])->name('home.legacy');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Migrated ERP pages. The .php routes keep existing in-page links working while
// clean Laravel URLs are available for the same pages.
foreach (onyx_legacy_pages() as $page) {
    if ($page !== 'assets') {
        Route::match(['GET', 'POST'], '/' . $page, [LegacyPageController::class, 'show'])
            ->defaults('page', $page)
            ->name('erp.' . $page);
    }

    Route::match(['GET', 'POST'], '/' . $page . '.php', [LegacyPageController::class, 'show'])
        ->defaults('page', $page)
        ->name('erp.' . $page . '.legacy');
}
