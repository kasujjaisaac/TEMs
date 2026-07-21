<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\LegacyPageController;
use App\Http\Controllers\Commercial\ActivityController as CommercialActivityController;
use App\Http\Controllers\Commercial\CampaignController as CommercialCampaignController;
use App\Http\Controllers\Commercial\DashboardController as CommercialDashboardController;
use App\Http\Controllers\Commercial\LeadController as CommercialLeadController;
use App\Http\Controllers\Commercial\MeetingController as CommercialMeetingController;
use App\Http\Controllers\Commercial\OpportunityController as CommercialOpportunityController;
use App\Http\Controllers\Commercial\OrganizationController as CommercialOrganizationController;
use App\Http\Controllers\Commercial\SiteVisitController as CommercialSiteVisitController;
use App\Http\Controllers\Commercial\StakeholderController as CommercialStakeholderController;
use App\Http\Controllers\CRM\CustomerAccountController as CrmCustomerAccountController;
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
use App\Http\Controllers\Enterprise\DeliveryController;
use App\Http\Controllers\Enterprise\CustomerSuccessController;
use App\Http\Controllers\Enterprise\EngineeringController;
use App\Http\Controllers\Enterprise\GovernanceController;
use App\Http\Controllers\Enterprise\IntelligenceController;
use App\Http\Controllers\Enterprise\KnowledgeController;
use App\Http\Controllers\Enterprise\MarketingController;
use App\Http\Controllers\Enterprise\ReportsController;
use App\Http\Controllers\Enterprise\StrategyController;

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
    Route::prefix('crm')->name('crm.')->group(function () {
        Route::get('/', [CrmCustomerAccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/accounts', [CrmCustomerAccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/{customer}', [CrmCustomerAccountController::class, 'show'])->name('accounts.show');
    });

    Route::prefix('commercial')->name('commercial.')->group(function () {
        Route::get('/', CommercialDashboardController::class)->name('dashboard');
        Route::get('/campaigns', [CommercialCampaignController::class, 'index'])->name('campaigns.index');
        Route::post('/campaigns', [CommercialCampaignController::class, 'store'])->name('campaigns.store');
        Route::post('/leads/{lead}/convert', [CommercialLeadController::class, 'convert'])->name('leads.convert');
        Route::resource('leads', CommercialLeadController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::post('/organizations/{organization}/sync-customer', [CommercialOrganizationController::class, 'syncCustomer'])->name('organizations.sync_customer');
        Route::resource('organizations', CommercialOrganizationController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::resource('stakeholders', CommercialStakeholderController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::patch('/opportunities/{opportunity}/stage', [CommercialOpportunityController::class, 'updateStage'])->name('opportunities.stage.update');
        Route::post('/opportunities/{opportunity}/proposals', [CommercialOpportunityController::class, 'storeProposal'])->name('opportunities.proposals.store');
        Route::post('/proposals/{proposal}/approve', [CommercialOpportunityController::class, 'approveProposal'])->name('proposals.approve');
        Route::post('/opportunities/{opportunity}/quotations', [CommercialOpportunityController::class, 'storeQuotation'])->name('opportunities.quotations.store');
        Route::post('/quotations/{quotation}/decision', [CommercialOpportunityController::class, 'decideQuotation'])->name('quotations.decision');
        Route::post('/opportunities/{opportunity}/contracts', [CommercialOpportunityController::class, 'storeContract'])->name('opportunities.contracts.store');
        Route::post('/contracts/{contract}/sign', [CommercialOpportunityController::class, 'signContract'])->name('contracts.sign');
        Route::post('/opportunities/{opportunity}/billing-requests', [CommercialOpportunityController::class, 'storeBillingRequest'])->name('opportunities.billing_requests.store');
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
        Route::post('/expenses', [FinanceBudgetController::class, 'storeExpense'])->name('expenses.store');
        Route::post('/purchase-requests', [FinanceBudgetController::class, 'storePurchaseRequest'])->name('purchase_requests.store');
        Route::post('/purchase-orders', [FinanceBudgetController::class, 'storePurchaseOrder'])->name('purchase_orders.store');
        Route::post('/supplier-bills', [FinanceBudgetController::class, 'storeSupplierBill'])->name('supplier_bills.store');
        Route::post('/payments', [FinanceBudgetController::class, 'storePayment'])->name('payments.store');
        Route::post('/assets', [FinanceBudgetController::class, 'storeAsset'])->name('assets.store');
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
        Route::post('/workplan-items/{item}/evidence', [PlanningWorkplanController::class, 'submitEvidence'])->name('workplan_items.evidence.store');
        Route::post('/evidence/{evidence}/review', [PlanningWorkplanController::class, 'reviewEvidence'])->name('evidence.review');
        Route::post('/workplan-items/{item}/corrective-actions', [PlanningWorkplanController::class, 'storeCorrectiveAction'])->name('workplan_items.corrective_actions.store');
    });

    Route::prefix('foundation')->name('foundation.')->group(function () {
        Route::get('/', [FoundationController::class, 'index'])->name('dashboard');
        Route::put('/company', [FoundationController::class, 'updateCompany'])->name('company.update');
        Route::post('/approvals', [FoundationController::class, 'storeApproval'])->name('approvals.store');
        Route::post('/approvals/{approval}/decision', [FoundationController::class, 'decideApproval'])->name('approvals.decision');
        Route::post('/notifications/{notification}/read', [FoundationController::class, 'markNotificationRead'])->name('notifications.read');
        Route::post('/employees', [FoundationController::class, 'storeEmployee'])->name('employees.store');
        Route::post('/approval-rules', [FoundationController::class, 'storeApprovalRule'])->name('approval_rules.store');
        Route::post('/notification-preferences', [FoundationController::class, 'storeNotificationPreference'])->name('notification_preferences.store');
        Route::post('/documents', [FoundationController::class, 'storeDocument'])->name('documents.store');
    });

    Route::prefix('strategy')->name('strategy.')->group(function () {
        Route::get('/', [StrategyController::class, 'index'])->name('dashboard');
        Route::post('/directives', [StrategyController::class, 'storeDirective'])->name('directives.store');
    });

    Route::prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/', [MarketingController::class, 'index'])->name('dashboard');
        Route::post('/plans', [MarketingController::class, 'storePlan'])->name('plans.store');
    });

    Route::prefix('delivery')->name('delivery.')->group(function () {
        Route::get('/', [DeliveryController::class, 'index'])->name('dashboard');
        Route::post('/products', [DeliveryController::class, 'storeProduct'])->name('products.store');
        Route::post('/projects/from-opportunity', [DeliveryController::class, 'createProjectFromOpportunity'])->name('projects.from_opportunity');
    });

    Route::prefix('engineering')->name('engineering.')->group(function () {
        Route::get('/', [EngineeringController::class, 'index'])->name('dashboard');
        Route::post('/backlog', [EngineeringController::class, 'storeBacklogItem'])->name('backlog.store');
    });

    Route::prefix('customer-success')->name('customer_success.')->group(function () {
        Route::get('/', [CustomerSuccessController::class, 'index'])->name('dashboard');
        Route::post('/tickets', [CustomerSuccessController::class, 'storeTicket'])->name('tickets.store');
    });

    Route::prefix('governance')->name('governance.')->group(function () {
        Route::get('/', [GovernanceController::class, 'index'])->name('dashboard');
        Route::post('/obligations', [GovernanceController::class, 'storeObligation'])->name('obligations.store');
    });

    Route::prefix('intelligence')->name('intelligence.')->group(function () {
        Route::get('/', [IntelligenceController::class, 'index'])->name('dashboard');
        Route::post('/refresh', [IntelligenceController::class, 'refresh'])->name('refresh');
    });

    Route::prefix('knowledge')->name('knowledge.')->group(function () {
        Route::get('/', [KnowledgeController::class, 'index'])->name('dashboard');
        Route::post('/articles', [KnowledgeController::class, 'storeArticle'])->name('articles.store');
    });

    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('dashboard');
        Route::post('/reports', [ReportsController::class, 'storeReport'])->name('reports.store');
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
// clean Laravel URLs are available for the same pages. CRM is now a native
// module at /crm, so only crm.php should remain on the legacy bridge.
foreach (onyx_legacy_pages() as $page) {
    if (! in_array($page, ['assets', 'crm'], true)) {
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
