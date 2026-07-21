<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use App\Services\CRM\CustomerIdentityService;
use App\Services\CRM\CustomerAccountLifecycleService;

class CustomerAccountController extends Controller
{
    public function dashboard(): View
    {
        $this->authorizeCrm();
        $tenantId = $this->tenantId();

        return view('crm.dashboard', [
            'page_title' => 'CRM & Customer Accounts | Texaro Technologies Limited',
            'metrics' => [
                'customer_accounts' => DB::table('customers')->where('tenant_id', $tenantId)->count(),
                'active_accounts' => DB::table('customers')->where('tenant_id', $tenantId)->where('is_active', true)->count(),
                'linked_commercial_accounts' => $this->hasColumn('customers', 'commercial_organization_id')
                    ? DB::table('customers')->where('tenant_id', $tenantId)->whereNotNull('commercial_organization_id')->count()
                    : 0,
                'open_opportunities' => $this->hasTable('commercial_opportunities')
                    ? DB::table('commercial_opportunities')->where('tenant_id', $tenantId)->whereNotIn('current_stage', ['Won', 'Lost'])->count()
                    : 0,
                'open_crm_leads' => $this->hasTable('crm_leads')
                    ? DB::table('crm_leads')->where('tenant_id', $tenantId)->whereNotIn('status', ['converted', 'lost'])->count()
                    : 0,
                'open_support_tickets' => $this->hasTable('support_tickets')
                    ? DB::table('support_tickets')->where('tenant_id', $tenantId)->whereNotIn('status', ['Closed', 'Resolved'])->count()
                    : 0,
                'identity_conflicts' => app(CustomerIdentityService::class)->conflictCount($tenantId),
            ],
            'recentAccounts' => DB::table('customers')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(8)
                ->get(),
            'recentOpportunities' => $this->recentCommercialOpportunities($tenantId),
        ]);
    }

    public function index(Request $request): View
    {
        $this->authorizeCrm();
        $tenantId = $this->tenantId();
        $search = $request->string('search')->toString();

        return view('crm.accounts.index', [
            'page_title' => 'Customer Accounts | Texaro Technologies Limited',
            'accounts' => DB::table('customers')
                ->where('tenant_id', $tenantId)
                ->when($search, function ($query, string $search): void {
                    $query->where(function ($nested) use ($search): void {
                        $nested->where('name', 'like', '%' . $search . '%')
                            ->orWhere('company_name', 'like', '%' . $search . '%')
                            ->orWhere('customer_code', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
                })
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(int $customer): View
    {
        $this->authorizeCrm();
        $tenantId = $this->tenantId();
        $account = DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customer)->first();
        abort_unless($account, 404);

        return view('crm.accounts.show', [
            'page_title' => ($account->company_name ?: $account->name) . ' | Customer 360',
            'account' => $account,
            'commercialOrganization' => $this->commercialOrganization($tenantId, $account),
            'opportunities' => $this->customerOpportunities($tenantId, $account),
            'invoices' => $this->customerInvoices($tenantId, $account->id),
            'payments' => $this->customerPayments($tenantId, $account->id),
            'crmLeads' => $this->customerCrmLeads($tenantId, $account->id),
            'identityLinks' => $this->customerIdentityLinks($tenantId, $account->id),
            'accountPlan' => DB::table('crm_account_plans')->where('tenant_id', $tenantId)->where('customer_id', $account->id)->first(),
            'timeline' => DB::table('crm_account_timeline')->where('tenant_id', $tenantId)->where('customer_id', $account->id)->latest('occurred_at')->limit(20)->get(),
            'healthSnapshots' => DB::table('crm_customer_health_snapshots')->where('tenant_id', $tenantId)->where('customer_id', $account->id)->latest('snapshot_date')->limit(8)->get(),
            'renewals' => DB::table('commercial_renewals')->where('tenant_id', $tenantId)->where('customer_id', $account->id)->latest('renewal_due_on')->limit(8)->get(),
            'expansions' => DB::table('commercial_expansion_opportunities')->where('tenant_id', $tenantId)->where('customer_id', $account->id)->latest()->limit(8)->get(),
            'branches' => DB::table('crm_customer_branches')->where('tenant_id', $tenantId)->where('customer_id', $account->id)->latest()->limit(10)->get(),
            'customerDocuments' => DB::table('crm_customer_documents')->where('tenant_id', $tenantId)->where('customer_id', $account->id)->latest()->limit(10)->get(),
            'subscriptions' => DB::table('crm_customer_subscriptions')->where('tenant_id', $tenantId)->where('customer_id', $account->id)->latest()->limit(10)->get(),
        ]);
    }

    public function storeAccountPlan(Request $request, int $customer, CustomerAccountLifecycleService $accounts)
    {
        abort_unless(Auth::user()?->hasPermission('crm.accounts.manage') || Auth::user()?->hasPermission('customers.manage'), 403);
        $data = $request->validate([
            'relationship_stage' => ['nullable', 'string', 'max:80'],
            'objectives' => ['nullable', 'string', 'max:3000'],
            'growth_strategy' => ['nullable', 'string', 'max:3000'],
            'retention_strategy' => ['nullable', 'string', 'max:3000'],
            'health_status' => ['nullable', 'string', 'max:60'],
            'risk_level' => ['nullable', 'string', 'max:40'],
            'next_review_on' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:60'],
        ]);

        $accounts->upsertAccountPlan($this->tenantId(), $customer, $request->user(), $data);

        return back()->with('success', 'Account plan saved.');
    }

    public function captureHealth(int $customer, CustomerAccountLifecycleService $accounts)
    {
        abort_unless(Auth::user()?->hasPermission('crm.health.view') || Auth::user()?->hasPermission('crm.accounts.manage'), 403);
        $accounts->captureHealth($this->tenantId(), $customer, Auth::user());

        return back()->with('success', 'Customer health captured.');
    }

    public function storeBranch(Request $request, int $customer, CustomerAccountLifecycleService $accounts)
    {
        abort_unless(Auth::user()?->hasPermission('crm.accounts.manage') || Auth::user()?->hasPermission('customers.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_type' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:2000'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'is_primary' => ['nullable', 'boolean'],
        ]);
        $accounts->addBranch($this->tenantId(), $customer, $request->user(), $data);

        return back()->with('success', 'Customer branch saved.');
    }

    public function storeDocument(Request $request, int $customer, CustomerAccountLifecycleService $accounts)
    {
        abort_unless(Auth::user()?->hasPermission('crm.accounts.manage') || Auth::user()?->hasPermission('customers.manage'), 403);
        $data = $request->validate([
            'document_type' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:60'],
            'expires_on' => ['nullable', 'date'],
            'storage_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $accounts->addDocument($this->tenantId(), $customer, $request->user(), $data);

        return back()->with('success', 'Customer document saved.');
    }

    public function storeSubscription(Request $request, int $customer, CustomerAccountLifecycleService $accounts)
    {
        abort_unless(Auth::user()?->hasPermission('crm.accounts.manage') || Auth::user()?->hasPermission('customers.manage'), 403);
        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'plan_name' => ['nullable', 'string', 'max:120'],
            'starts_on' => ['nullable', 'date'],
            'renews_on' => ['nullable', 'date'],
            'recurring_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'billing_frequency' => ['nullable', 'string', 'max:80'],
        ]);
        $accounts->addSubscription($this->tenantId(), $customer, $request->user(), $data);

        return back()->with('success', 'Customer subscription saved.');
    }

    private function authorizeCrm(): void
    {
        abort_unless(Auth::user()?->hasPermission('crm.accounts.view') || Auth::user()?->hasPermission('customers.view'), 403);
    }

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    private function recentCommercialOpportunities(int $tenantId)
    {
        if (! $this->hasTable('commercial_opportunities')) {
            return collect();
        }

        return DB::table('commercial_opportunities')
            ->leftJoin('commercial_organizations', 'commercial_organizations.id', '=', 'commercial_opportunities.organization_id')
            ->where('commercial_opportunities.tenant_id', $tenantId)
            ->select([
                'commercial_opportunities.id',
                'commercial_opportunities.reference',
                'commercial_opportunities.title',
                'commercial_opportunities.current_stage',
                'commercial_opportunities.estimated_value',
                'commercial_opportunities.currency',
                'commercial_organizations.legal_name as organization_name',
            ])
            ->latest('commercial_opportunities.updated_at')
            ->limit(8)
            ->get();
    }

    private function commercialOrganization(int $tenantId, object $account): ?object
    {
        if (! $this->hasTable('commercial_organizations')) {
            return null;
        }

        if ($this->hasColumn('customers', 'commercial_organization_id') && $account->commercial_organization_id) {
            return DB::table('commercial_organizations')
                ->where('tenant_id', $tenantId)
                ->where('id', $account->commercial_organization_id)
                ->first();
        }

        return DB::table('commercial_organizations')
            ->where('tenant_id', $tenantId)
            ->where('legacy_customer_id', $account->id)
            ->first();
    }

    private function customerOpportunities(int $tenantId, object $account)
    {
        if (! $this->hasTable('commercial_opportunities') || ! $this->hasTable('commercial_organizations')) {
            return collect();
        }

        return DB::table('commercial_opportunities')
            ->join('commercial_organizations', 'commercial_organizations.id', '=', 'commercial_opportunities.organization_id')
            ->where('commercial_opportunities.tenant_id', $tenantId)
            ->where(function ($query) use ($account): void {
                $query->where('commercial_organizations.legacy_customer_id', $account->id);
                if ($this->hasColumn('customers', 'commercial_organization_id') && $account->commercial_organization_id) {
                    $query->orWhere('commercial_organizations.id', $account->commercial_organization_id);
                }
            })
            ->select('commercial_opportunities.*')
            ->selectRaw('(commercial_opportunities.estimated_value * commercial_opportunities.probability / 100.0) as weighted_value')
            ->latest('commercial_opportunities.updated_at')
            ->get();
    }

    private function customerInvoices(int $tenantId, int $customerId)
    {
        if (! $this->hasTable('invoices')) {
            return collect();
        }

        return DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->latest('invoice_date')
            ->limit(10)
            ->get();
    }

    private function customerPayments(int $tenantId, int $customerId)
    {
        if (! $this->hasTable('invoice_payments') || ! $this->hasTable('invoices')) {
            return collect();
        }

        return DB::table('invoice_payments')
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->where('invoice_payments.tenant_id', $tenantId)
            ->where('invoices.customer_id', $customerId)
            ->select('invoice_payments.*', 'invoices.invoice_number')
            ->latest('invoice_payments.payment_date')
            ->limit(10)
            ->get();
    }

    private function customerCrmLeads(int $tenantId, int $customerId)
    {
        if (! $this->hasTable('crm_leads')) {
            return collect();
        }

        return DB::table('crm_leads')
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($customerId): void {
                $query->where('customer_id', $customerId)
                    ->orWhere('converted_customer_id', $customerId);
            })
            ->latest()
            ->limit(10)
            ->get();
    }

    private function customerIdentityLinks(int $tenantId, int $customerId)
    {
        if (! $this->hasTable('customer_identity_links')) {
            return collect();
        }

        return DB::table('customer_identity_links')
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->latest('linked_at')
            ->get();
    }
}
