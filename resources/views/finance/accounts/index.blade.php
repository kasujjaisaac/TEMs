@extends('layouts.app')

@section('content')
@include('finance.partials.style')
<section class="finance-page">
    <header class="finance-header">
        <div class="finance-title"><div class="finance-icon"><i class="fa-solid fa-layer-group"></i></div><div><h1>Chart of Accounts</h1><div class="finance-muted">Double-entry classification foundation for all financial activity.</div></div></div>
        <a class="finance-button secondary" href="{{ route('finance.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </header>
    <section class="finance-panel">
        @include('finance.partials.table', [
            'headers' => ['Code', 'Account', 'Type', 'Normal Balance', 'Control', 'Cash', 'Status'],
            'rows' => $accounts->map(fn ($account) => [
                '<strong class="finance-table-title">' . e($account->code) . '</strong>',
                e($account->name),
                e($account->type),
                e($account->normal_balance),
                $account->is_control_account ? '<span class="finance-badge success">Control</span>' : '-',
                $account->is_cash_account ? '<span class="finance-badge success">Cash</span>' : '-',
                $account->is_active ? '<span class="finance-badge success">Active</span>' : '<span class="finance-badge danger">Inactive</span>',
            ])->all()
        ])
    </section>
</section>
@endsection
