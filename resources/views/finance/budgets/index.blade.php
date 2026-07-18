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
</section>
@endsection
