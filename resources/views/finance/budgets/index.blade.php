@extends('layouts.app')

@section('content')
@include('finance.partials.style')
<section class="finance-page">
    <header class="finance-header">
        <div class="finance-title"><div class="finance-icon"><i class="fa-solid fa-wallet"></i></div><div><h1>Budget Control</h1><div class="finance-muted">Approved budgets, commitments, actuals, available balances and variance discipline.</div></div></div>
        <a class="finance-button secondary" href="{{ route('finance.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>
    @if(session('success')) <div class="finance-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="finance-alert danger">{{ $errors->first() }}</div> @endif

    @if(auth()->user()?->hasPermission('finance.budgets.manage'))
        <section class="finance-panel">
            <div class="finance-panel-head"><h2>Create Budget Line</h2><span class="finance-muted">Financial year {{ $fiscalYear->name }}</span></div>
            <form class="finance-form" method="POST" action="{{ route('finance.budgets.store') }}">
                @csrf
                <div class="finance-field"><label>Reference</label><input name="reference" value="{{ old('reference') }}" placeholder="BUD-HR-001" required></div>
                <div class="finance-field double"><label>Description</label><input name="description" value="{{ old('description') }}" required></div>
                <div class="finance-field"><label>Status</label><select name="status">@foreach(['Draft','Submitted','Approved','Frozen','Closed'] as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select></div>
                <div class="finance-field"><label>Account</label><select name="account_id" required>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>@endforeach</select></div>
                <div class="finance-field"><label>Cost Centre</label><select name="cost_centre_id"><option value="">General</option>@foreach($costCentres as $centre)<option value="{{ $centre->id }}">{{ $centre->code }} - {{ $centre->name }}</option>@endforeach</select></div>
                <div class="finance-field"><label>Annual Budget</label><input type="number" step="0.01" min="0" name="annual_budget" value="{{ old('annual_budget', 0) }}" required></div>
                <div class="finance-field"><label>Monthly Allocation</label><input type="number" step="0.01" min="0" name="monthly_allocation" value="{{ old('monthly_allocation', 0) }}"></div>
                <div class="finance-field double"><label>Workplan Objective</label><input name="workplan_objective" value="{{ old('workplan_objective') }}"></div>
                <div class="finance-field"><label>Owner</label><input name="owner_name" value="{{ old('owner_name') }}"></div>
                <div class="finance-field"><label>Approver</label><input name="approver_name" value="{{ old('approver_name') }}"></div>
                <div class="finance-field full"><button class="finance-button" type="submit"><i class="fa-solid fa-check"></i> Save Budget Line</button></div>
            </form>
        </section>
    @endif

    @if(auth()->user()?->hasPermission('finance.budgets.manage'))
        <section class="finance-split">
            <div class="finance-panel">
                <div class="finance-panel-head"><h2>Expense Control</h2></div>
                <form class="finance-form" method="POST" action="{{ route('finance.expenses.store') }}">
                    @csrf
                    <div class="finance-field"><label>Budget Line</label><select name="budget_line_id"><option value="">None</option>@foreach($budgetLines as $line)<option value="{{ $line->id }}">{{ $line->reference }}</option>@endforeach</select></div>
                    <div class="finance-field"><label>Amount</label><input name="amount" type="number" min="0.01" step="0.01" required></div>
                    <div class="finance-field"><label>Date</label><input name="expense_date" type="date" value="{{ now()->toDateString() }}"></div>
                    <div class="finance-field full"><label>Description</label><input name="description" required></div>
                    <div class="finance-field full"><button class="finance-button" type="submit">Submit Expense</button></div>
                </form>
            </div>
            <div class="finance-panel">
                <div class="finance-panel-head"><h2>Purchase Request</h2></div>
                <form class="finance-form" method="POST" action="{{ route('finance.purchase_requests.store') }}">
                    @csrf
                    <div class="finance-field"><label>Budget Line</label><select name="budget_line_id"><option value="">None</option>@foreach($budgetLines as $line)<option value="{{ $line->id }}">{{ $line->reference }}</option>@endforeach</select></div>
                    <div class="finance-field"><label>Estimated Amount</label><input name="estimated_amount" type="number" min="0" step="0.01" value="0"></div>
                    <div class="finance-field full"><label>Title</label><input name="title" required></div>
                    <div class="finance-field full"><label>Justification</label><textarea name="justification"></textarea></div>
                    <div class="finance-field full"><button class="finance-button" type="submit">Submit Request</button></div>
                </form>
            </div>
        </section>

        <section class="finance-split">
            <div class="finance-panel">
                <div class="finance-panel-head"><h2>Purchase Order & Bill</h2></div>
                <form class="finance-form" method="POST" action="{{ route('finance.purchase_orders.store') }}">
                    @csrf
                    <div class="finance-field"><label>Supplier</label><input name="supplier_name" required></div>
                    <div class="finance-field"><label>Total</label><input name="total_amount" type="number" min="0" step="0.01" required></div>
                    <div class="finance-field full"><button class="finance-button secondary" type="submit">Issue PO</button></div>
                </form>
                <form class="finance-form" method="POST" action="{{ route('finance.supplier_bills.store') }}" style="margin-top:12px">
                    @csrf
                    <div class="finance-field"><label>Supplier</label><input name="supplier_name" required></div>
                    <div class="finance-field"><label>Amount</label><input name="amount" type="number" min="0.01" step="0.01" required></div>
                    <div class="finance-field"><label>Bill Date</label><input name="bill_date" type="date" value="{{ now()->toDateString() }}"></div>
                    <div class="finance-field full"><button class="finance-button secondary" type="submit">Record Bill</button></div>
                </form>
            </div>
            <div class="finance-panel">
                <div class="finance-panel-head"><h2>Payment & Asset</h2></div>
                <form class="finance-form" method="POST" action="{{ route('finance.payments.store') }}">
                    @csrf
                    <div class="finance-field"><label>Payee</label><input name="payee_name" required></div>
                    <div class="finance-field"><label>Amount</label><input name="amount" type="number" min="0.01" step="0.01" required></div>
                    <div class="finance-field"><label>Method</label><input name="method" placeholder="Bank, cash, mobile money"></div>
                    <div class="finance-field full"><button class="finance-button" type="submit">Record Payment</button></div>
                </form>
                <form class="finance-form" method="POST" action="{{ route('finance.assets.store') }}" style="margin-top:12px">
                    @csrf
                    <div class="finance-field"><label>Asset</label><input name="name" required></div>
                    <div class="finance-field"><label>Category</label><input name="category"></div>
                    <div class="finance-field"><label>Cost</label><input name="cost" type="number" min="0" step="0.01" value="0"></div>
                    <div class="finance-field full"><button class="finance-button secondary" type="submit">Register Asset</button></div>
                </form>
            </div>
        </section>
    @endif

    <section class="finance-panel">
        <div class="finance-panel-head"><h2>Budget Lines</h2></div>
        @include('finance.partials.table', [
            'headers' => ['Line', 'Account', 'Cost Centre', 'Budget', 'Committed', 'Actual', 'Available', 'Status'],
            'rows' => $budgetLines->map(fn ($line) => [
                '<strong class="finance-table-title">' . e($line->reference) . '</strong><span class="finance-muted">' . e($line->description) . '</span>',
                e($line->account?->name ?: '-'),
                e($line->costCentre?->name ?: '-'),
                e(number_format((float) $line->annual_budget, 2)),
                e(number_format((float) $line->committed_amount, 2)),
                e(number_format((float) $line->actual_spent, 2)),
                e(number_format($line->available_balance, 2)),
                '<span class="finance-badge ' . ($line->status === 'Approved' ? 'success' : 'warning') . '">' . e($line->status) . '</span>',
            ])->all()
        ])
        <div style="margin-top:12px">{{ $budgetLines->links() }}</div>
    </section>

    <section class="finance-panel">
        <div class="finance-panel-head"><h2>Procurement & Asset Registers</h2></div>
        @include('finance.partials.table', [
            'headers' => ['Register', 'Reference', 'Description', 'Amount', 'Status'],
            'rows' => collect()
                ->merge($expenses->map(fn($x) => ['Expense', e($x->reference), e($x->description), e(number_format((float)$x->amount, 2)), e($x->status)]))
                ->merge($purchaseRequests->map(fn($x) => ['Purchase Request', e($x->reference), e($x->title), e(number_format((float)$x->estimated_amount, 2)), e($x->status)]))
                ->merge($purchaseOrders->map(fn($x) => ['Purchase Order', e($x->reference), e($x->supplier_name), e(number_format((float)$x->total_amount, 2)), e($x->status)]))
                ->merge($supplierBills->map(fn($x) => ['Supplier Bill', e($x->reference), e($x->supplier_name), e(number_format((float)$x->amount, 2)), e($x->status)]))
                ->merge($payments->map(fn($x) => ['Payment', e($x->reference), e($x->payee_name), e(number_format((float)$x->amount, 2)), e($x->status)]))
                ->merge($assets->map(fn($x) => ['Asset', e($x->reference), e($x->name), e(number_format((float)$x->cost, 2)), e($x->status)]))
                ->values()->all()
        ])
    </section>
</section>
@endsection
