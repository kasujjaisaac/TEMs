<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\LegacyPageController;
use App\Http\Controllers\Commercial\ActivityController as CommercialActivityController;
use App\Http\Controllers\Commercial\DashboardController as CommercialDashboardController;
use App\Http\Controllers\Commercial\LeadController as CommercialLeadController;
use App\Http\Controllers\Commercial\MeetingController as CommercialMeetingController;
use App\Http\Controllers\Commercial\OpportunityController as CommercialOpportunityController;
use App\Http\Controllers\Commercial\OrganizationController as CommercialOrganizationController;
use App\Http\Controllers\Commercial\SiteVisitController as CommercialSiteVisitController;
use App\Http\Controllers\Commercial\StakeholderController as CommercialStakeholderController;
use App\Http\Controllers\Finance\AccountController as FinanceAccountController;
use App\Http\Controllers\Finance\BudgetController as FinanceBudgetController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Finance\TransactionController as FinanceTransactionController;
use App\Http\Controllers\HR\CommandCentreController as HrCommandCentreController;
use App\Http\Controllers\HR\DepartmentController as HrDepartmentController;
use App\Http\Controllers\HR\PositionController as HrPositionController;
use App\Http\Controllers\Planning\DashboardController as PlanningDashboardController;
use App\Http\Controllers\Planning\ObjectiveController as PlanningObjectiveController;
use App\Http\Controllers\Planning\WorkplanController as PlanningWorkplanController;
use App\Http\Controllers\Enterprise\FoundationController;

Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/index.php', [AuthController::class, 'showLogin'])->name('home.legacy');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login/otp', [AuthController::class, 'showOtp'])->name('login.otp');
Route::post('/login/otp', [AuthController::class, 'verifyOtp'])->name('login.otp.verify');
Route::post('/login/otp/resend', [AuthController::class, 'resendOtp'])->name('login.otp.resend');
Route::get('/password/forgot', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/password/email', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
Route::get('/password/reset/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset.update');
Route::get('/register', fn () => redirect()->route('login'));
Route::post('/register', fn () => redirect()->route('login'));
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.update');
});

Route::middleware(['auth', 'password.changed'])->group(function () {
    Route::prefix('commercial')->name('commercial.')->group(function () {
        Route::get('/', CommercialDashboardController::class)->name('dashboard');
        Route::post('/leads/{lead}/convert', [CommercialLeadController::class, 'convert'])->name('leads.convert');
        Route::resource('leads', CommercialLeadController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::resource('organizations', CommercialOrganizationController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::resource('stakeholders', CommercialStakeholderController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/opportunities/{opportunity}/stage', [CommercialOpportunityController::class, 'updateStage'])->name('opportunities.stage.update');
        Route::post('/opportunities/{opportunity}/handoff-to-sales', [CommercialOpportunityController::class, 'handoffToSales'])->name('opportunities.handoff_to_sales');
        Route::resource('opportunities', CommercialOpportunityController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('activities', CommercialActivityController::class)->only(['index', 'create', 'store']);
        Route::resource('meetings', CommercialMeetingController::class)->only(['index', 'create', 'store']);
        Route::resource('site-visits', CommercialSiteVisitController::class)->only(['index', 'create', 'store'])->names('site_visits');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', FinanceDashboardController::class)->name('dashboard');
        Route::post('/sync', [FinanceDashboardController::class, 'sync'])->name('sync');
        Route::get('/accounts', [FinanceAccountController::class, 'index'])->name('accounts.index');
        Route::get('/budgets', [FinanceBudgetController::class, 'index'])->name('budgets.index');
        Route::post('/budgets', [FinanceBudgetController::class, 'store'])->name('budgets.store');
        Route::get('/transactions', [FinanceTransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{transaction}', [FinanceTransactionController::class, 'show'])->name('transactions.show');
    });

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/', HrCommandCentreController::class)->name('command');
        Route::resource('departments', HrDepartmentController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::resource('positions', HrPositionController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    });

    Route::prefix('planning')->name('planning.')->group(function () {
        Route::get('/', PlanningDashboardController::class)->name('dashboard');
        Route::get('/objectives', [PlanningObjectiveController::class, 'index'])->name('objectives.index');
        Route::post('/objectives', [PlanningObjectiveController::class, 'store'])->name('objectives.store');
        Route::get('/workplans', [PlanningWorkplanController::class, 'index'])->name('workplans.index');
        Route::get('/workplans/{workplan}', [PlanningWorkplanController::class, 'show'])->name('workplans.show');
        Route::post('/workplans/{workplan}/items', [PlanningWorkplanController::class, 'storeItem'])->name('workplans.items.store');
        Route::post('/workplans/{workplan}/approve', [PlanningWorkplanController::class, 'approve'])->name('workplans.approve');
    });

    Route::prefix('foundation')->name('foundation.')->group(function () {
        Route::get('/', [FoundationController::class, 'index'])->name('dashboard');
        Route::put('/company', [FoundationController::class, 'updateCompany'])->name('company.update');
        Route::post('/approvals', [FoundationController::class, 'storeApproval'])->name('approvals.store');
        Route::post('/approvals/{approval}/decision', [FoundationController::class, 'decideApproval'])->name('approvals.decision');
        Route::post('/notifications/{notification}/read', [FoundationController::class, 'markNotificationRead'])->name('notifications.read');
    });

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
