@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <h1>Customer Accounts</h1>
                <div class="commercial-muted">The CRM account register uses the shared customer record after commercial conversion or sales handoff.</div>
            </div>
        </div>
        <div class="commercial-actions">
            <a class="commercial-button secondary" href="{{ route('crm.dashboard') }}"><i class="fa-solid fa-arrow-left"></i> CRM Dashboard</a>
            @if(auth()->user()?->hasPermission('commercial.organizations.create'))
                <a class="commercial-button" href="{{ route('commercial.organizations.create') }}"><i class="fa-solid fa-building-circle-arrow-right"></i> New Prospect</a>
            @endif
        </div>
    </header>

    <section class="commercial-panel">
        <form class="commercial-filters" method="GET" action="{{ route('crm.accounts.index') }}">
            <label class="commercial-field" style="min-width:260px;">
                <span class="commercial-muted">Search customer accounts</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Name, code, email, or phone">
            </label>
            <button class="commercial-button" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
    </section>

    <section class="commercial-panel">
        <div class="commercial-panel-head">
            <h2>Account Register</h2>
            <span class="commercial-muted">{{ $accounts->total() }} accounts</span>
        </div>
        @include('commercial.partials.table', [
            'headers' => ['Account', 'Primary Contact', 'Commercial Link', 'Credit / Terms', 'Status'],
            'rows' => $accounts->map(fn ($account) => [
                '<strong class="commercial-table-title"><a href="' . route('crm.accounts.show', $account->id) . '">' . e($account->company_name ?: $account->name) . '</a></strong><span class="commercial-muted">' . e($account->customer_code ?: 'No account code') . '</span>',
                e($account->contact_person ?: '-') . '<br><span class="commercial-muted">' . e($account->phone ?: $account->email ?: '-') . '</span>',
                e(($account->commercial_reference ?? null) ?: (($account->customer_source ?? null) ?: '-')),
                e(($account->credit_status ?: '-') . ' / ' . ($account->payment_terms ?: '-')),
                '<span class="commercial-badge ' . ($account->is_active ? 'success' : 'warning') . '">' . ($account->is_active ? 'Active' : 'Inactive') . '</span>',
            ])->all()
        ])

        <div style="margin-top:12px;">{{ $accounts->links() }}</div>
    </section>
</section>
@endsection
