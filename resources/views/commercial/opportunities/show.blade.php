@extends('layouts.app')

@section('content')
@include('commercial.partials.style')

<section class="commercial-page">
    <header class="commercial-header">
        <div class="commercial-title">
            <div class="commercial-title-icon"><i class="fa-solid fa-handshake-angle"></i></div>
            <div>
                <h1>{{ $opportunity->reference }}</h1>
                <div class="commercial-muted">{{ $opportunity->title }} · {{ $opportunity->current_stage }}</div>
            </div>
        </div>
        <div class="commercial-actions">
            <a class="commercial-button secondary" href="{{ route('commercial.opportunities.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a class="commercial-button secondary" href="{{ route('commercial.activities.create', ['related_type' => App\Models\Commercial\CommercialOpportunity::class, 'related_id' => $opportunity->id]) }}"><i class="fa-solid fa-list-check"></i> Activity</a>
        </div>
    </header>

    @if(session('success')) <div class="commercial-alert success">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="commercial-alert error">{{ $errors->first() }}</div> @endif

    <section class="commercial-grid">
        <div class="commercial-card"><span>Organization</span><strong>{{ $opportunity->organization?->legal_name }}</strong></div>
        <div class="commercial-card"><span>Stage</span><strong>{{ $opportunity->current_stage }}</strong></div>
        <div class="commercial-card"><span>Probability</span><strong>{{ $opportunity->probability }}%</strong></div>
        <div class="commercial-card"><span>Weighted Value</span><strong>{{ $opportunity->currency }} {{ number_format($opportunity->weighted_value, 2) }}</strong></div>
    </section>

    <section class="commercial-panel">
        <div class="commercial-panel-head"><h2>Revenue Lifecycle</h2><span class="commercial-muted">Proposal to quotation to contract to billing request.</span></div>
        @include('commercial.partials.table', [
            'headers' => ['Record', 'Value', 'Status', 'Date', 'Action'],
            'rows' => collect()
                ->merge($opportunity->proposals->map(fn ($proposal) => [
                    '<strong class="commercial-table-title">' . e($proposal->reference) . '</strong><span class="commercial-muted">Proposal - ' . e($proposal->title) . '</span>',
                    e($proposal->currency . ' ' . number_format((float) $proposal->proposed_value, 2)),
                    '<span class="commercial-badge ' . ($proposal->status === 'Approved' ? 'success' : 'warning') . '">' . e($proposal->status) . '</span>',
                    e($proposal->created_at?->format('M d, Y') ?: '-'),
                    $proposal->status !== 'Approved' && auth()->user()?->hasPermission('commercial.opportunities.change_stage') ? '<form method="POST" action="' . route('commercial.proposals.approve', $proposal) . '">' . csrf_field() . '<button class="commercial-button" type="submit">Approve</button></form>' : '-',
                ]))
                ->merge($opportunity->quotations->map(fn ($quotation) => [
                    '<strong class="commercial-table-title">' . e($quotation->reference) . '</strong><span class="commercial-muted">Quotation</span>',
                    e($quotation->currency . ' ' . number_format((float) $quotation->total, 2)),
                    '<span class="commercial-badge ' . (in_array($quotation->status, ['Approved','Accepted'], true) ? 'success' : 'warning') . '">' . e($quotation->status) . '</span>',
                    e($quotation->quotation_date?->format('M d, Y') ?: '-'),
                    auth()->user()?->hasPermission('commercial.opportunities.change_stage') ? '<form class="commercial-inline-form" method="POST" action="' . route('commercial.quotations.decision', $quotation) . '">' . csrf_field() . '<input type="hidden" name="decision" value="Approved"><button class="commercial-button" type="submit">Approve</button></form><form class="commercial-inline-form" method="POST" action="' . route('commercial.quotations.decision', $quotation) . '">' . csrf_field() . '<input type="hidden" name="decision" value="Accepted"><button class="commercial-button secondary" type="submit">Accept</button></form>' : '-',
                ]))
                ->merge($opportunity->contracts->map(fn ($contract) => [
                    '<strong class="commercial-table-title">' . e($contract->reference) . '</strong><span class="commercial-muted">Contract - ' . e($contract->contract_title) . '</span>',
                    e($contract->currency . ' ' . number_format((float) $contract->contract_value, 2)),
                    '<span class="commercial-badge ' . ($contract->status === 'Signed' ? 'success' : 'warning') . '">' . e($contract->status) . '</span>',
                    e($contract->signed_at?->format('M d, Y') ?: ($contract->created_at?->format('M d, Y') ?: '-')),
                    $contract->status !== 'Signed' && auth()->user()?->hasPermission('commercial.opportunities.change_stage') ? '<form method="POST" action="' . route('commercial.contracts.sign', $contract) . '">' . csrf_field() . '<button class="commercial-button" type="submit">Sign</button></form>' : '-',
                ]))
                ->merge($opportunity->billingRequests->map(fn ($billing) => [
                    '<strong class="commercial-table-title">' . e($billing->reference) . '</strong><span class="commercial-muted">Billing Request</span>',
                    e($billing->currency . ' ' . number_format((float) $billing->amount, 2)),
                    '<span class="commercial-badge warning">' . e($billing->status) . '</span>',
                    e($billing->requested_invoice_date?->format('M d, Y') ?: '-'),
                    '-',
                ]))
                ->values()
                ->all()
        ])
    </section>

    @if(auth()->user()?->hasPermission('commercial.opportunities.update'))
        <section class="commercial-split">
            <div class="commercial-panel">
                <div class="commercial-panel-head"><h2>Create Proposal</h2></div>
                <form class="commercial-form" method="POST" action="{{ route('commercial.opportunities.proposals.store', $opportunity) }}">
                    @csrf
                    <div class="commercial-field double"><label>Title</label><input name="title" value="{{ $opportunity->title }} Proposal" required></div>
                    <div class="commercial-field"><label>Version</label><input name="version" value="1.0"></div>
                    <div class="commercial-field"><label>Value</label><input name="proposed_value" type="number" min="0" step="0.01" value="{{ $opportunity->estimated_value }}"></div>
                    <div class="commercial-field full"><label>Scope Summary</label><textarea name="scope_summary">{{ $opportunity->proposed_solution }}</textarea></div>
                    <div class="commercial-field full"><label>Value Proposition</label><textarea name="value_proposition">{{ $opportunity->customer_need }}</textarea></div>
                    <div class="commercial-field full"><button class="commercial-button" type="submit">Create Proposal</button></div>
                </form>
            </div>
            <div class="commercial-panel">
                <div class="commercial-panel-head"><h2>Create Quotation</h2></div>
                <form class="commercial-form" method="POST" action="{{ route('commercial.opportunities.quotations.store', $opportunity) }}">
                    @csrf
                    <div class="commercial-field"><label>Proposal</label><select name="proposal_id"><option value="">None</option>@foreach($opportunity->proposals as $proposal)<option value="{{ $proposal->id }}">{{ $proposal->reference }}</option>@endforeach</select></div>
                    <div class="commercial-field"><label>Subtotal</label><input name="subtotal" type="number" min="0" step="0.01" value="{{ $opportunity->estimated_value }}"></div>
                    <div class="commercial-field"><label>Discount</label><input name="discount_amount" type="number" min="0" step="0.01" value="0"></div>
                    <div class="commercial-field"><label>Tax</label><input name="tax_amount" type="number" min="0" step="0.01" value="0"></div>
                    <div class="commercial-field"><label>Date</label><input name="quotation_date" type="date" value="{{ now()->toDateString() }}"></div>
                    <div class="commercial-field"><label>Valid Until</label><input name="valid_until" type="date" value="{{ now()->addDays(14)->toDateString() }}"></div>
                    <div class="commercial-field full"><label>Terms</label><textarea name="terms">{{ $opportunity->organization?->payment_terms }}</textarea></div>
                    <div class="commercial-field full"><button class="commercial-button" type="submit">Create Quotation</button></div>
                </form>
            </div>
        </section>

        <section class="commercial-split">
            <div class="commercial-panel">
                <div class="commercial-panel-head"><h2>Create Contract</h2></div>
                <form class="commercial-form" method="POST" action="{{ route('commercial.opportunities.contracts.store', $opportunity) }}">
                    @csrf
                    <div class="commercial-field"><label>Quotation</label><select name="quotation_id"><option value="">None</option>@foreach($opportunity->quotations as $quotation)<option value="{{ $quotation->id }}">{{ $quotation->reference }}</option>@endforeach</select></div>
                    <div class="commercial-field double"><label>Title</label><input name="contract_title" value="{{ $opportunity->title }} Contract" required></div>
                    <div class="commercial-field"><label>Value</label><input name="contract_value" type="number" min="0" step="0.01" value="{{ $opportunity->estimated_value }}"></div>
                    <div class="commercial-field"><label>Starts On</label><input name="starts_on" type="date" value="{{ now()->toDateString() }}"></div>
                    <div class="commercial-field"><label>Ends On</label><input name="ends_on" type="date"></div>
                    <div class="commercial-field full"><label>Payment Terms</label><input name="payment_terms" value="{{ $opportunity->organization?->payment_terms }}"></div>
                    <div class="commercial-field full"><button class="commercial-button" type="submit">Create Contract</button></div>
                </form>
            </div>
            <div class="commercial-panel">
                <div class="commercial-panel-head"><h2>Billing Request</h2></div>
                <form class="commercial-form" method="POST" action="{{ route('commercial.opportunities.billing_requests.store', $opportunity) }}">
                    @csrf
                    <div class="commercial-field"><label>Contract</label><select name="contract_id"><option value="">None</option>@foreach($opportunity->contracts as $contract)<option value="{{ $contract->id }}">{{ $contract->reference }}</option>@endforeach</select></div>
                    <div class="commercial-field"><label>Quotation</label><select name="quotation_id"><option value="">None</option>@foreach($opportunity->quotations as $quotation)<option value="{{ $quotation->id }}">{{ $quotation->reference }}</option>@endforeach</select></div>
                    <div class="commercial-field"><label>Amount</label><input name="amount" type="number" min="0.01" step="0.01" value="{{ $opportunity->estimated_value }}" required></div>
                    <div class="commercial-field"><label>Invoice Date</label><input name="requested_invoice_date" type="date" value="{{ now()->toDateString() }}"></div>
                    <div class="commercial-field full"><label>Billing Terms</label><input name="billing_terms" value="{{ $opportunity->organization?->payment_terms }}"></div>
                    <div class="commercial-field full"><label>Instructions</label><textarea name="instructions"></textarea></div>
                    <div class="commercial-field full"><button class="commercial-button" type="submit">Request Billing</button></div>
                </form>
            </div>
        </section>
    @endif

    @if(auth()->user()?->hasPermission('commercial.opportunities.change_stage'))
        <section class="commercial-panel">
            <div class="commercial-panel-head"><h2>Stage Control</h2><span class="commercial-muted">Stage movement updates probability and writes history.</span></div>
            <form class="commercial-form" method="POST" action="{{ route('commercial.opportunities.stage.update', $opportunity) }}">
                @csrf
                @method('PATCH')
                <div class="commercial-field">
                    <label>Move To Stage</label>
                    <select name="stage_id" required>
                        @foreach($stages as $stage)
                            <option value="{{ $stage->id }}" @selected($opportunity->pipeline_stage_id === $stage->id || $opportunity->current_stage === $stage->name)>{{ $stage->name }} · {{ $stage->default_probability }}%</option>
                        @endforeach
                    </select>
                </div>
                <div class="commercial-field"><label>Reason</label><input name="reason" value="{{ old('reason') }}" placeholder="Customer confirmed next step"></div>
                <div class="commercial-field"><label>Notes</label><input name="notes" value="{{ old('notes') }}" placeholder="Optional context"></div>
                <div class="commercial-field full"><button class="commercial-button" type="submit"><i class="fa-solid fa-arrow-right-arrow-left"></i> Update Stage</button></div>
            </form>
        </section>
    @endif

    @if(auth()->user()?->hasPermission('commercial.opportunities.handoff_to_sales'))
        <section class="commercial-panel">
            <div class="commercial-panel-head">
                <h2>Sales Handoff</h2>
                <span class="commercial-muted">Create the Sales customer and draft quotation directly from this opportunity.</span>
            </div>

            @if($opportunity->latestSalesHandoff)
                @php $handoff = $opportunity->latestSalesHandoff; @endphp
                @include('commercial.partials.table', [
                    'headers' => ['Status', 'Customer', 'Quotation', 'Value', 'Handed Off'],
                    'rows' => [[
                        e($handoff->status),
                        $handoff->legacy_customer_id ? '<a href="' . url('customers_action?action=view&id=' . $handoff->legacy_customer_id) . '">Customer #' . e($handoff->legacy_customer_id) . '</a>' : '-',
                        $handoff->quotation_id ? '<a href="' . url('sales_action?action=view_invoice&id=' . $handoff->quotation_id) . '">Quotation #' . e($handoff->quotation_id) . '</a>' : '-',
                        e($handoff->currency . ' ' . number_format((float) $handoff->handoff_value, 2)),
                        e($handoff->handed_off_at?->format('M d, Y H:i') ?: '-'),
                    ]]
                ])
            @endif

            <form class="commercial-form" method="POST" action="{{ route('commercial.opportunities.handoff_to_sales', $opportunity) }}">
                @csrf
                <div class="commercial-field full">
                    <label>Handoff Summary</label>
                    <textarea name="handoff_summary" placeholder="Customer need, decision process, success criteria, and what Sales must protect.">{{ old('handoff_summary', $opportunity->customer_need) }}</textarea>
                </div>
                <div class="commercial-field full">
                    <label>Sales Instructions</label>
                    <textarea name="sales_instructions" placeholder="Pricing limits, approval notes, billing terms, implementation cautions, and next follow-up.">{{ old('sales_instructions', $opportunity->next_action) }}</textarea>
                </div>
                <div class="commercial-field full">
                    <label>Quotation Notes</label>
                    <textarea name="quotation_notes" placeholder="Notes that should appear on the draft quotation.">{{ old('quotation_notes', $opportunity->proposed_solution) }}</textarea>
                </div>
                <div class="commercial-field full">
                    <button class="commercial-button" type="submit"><i class="fa-solid fa-file-invoice-dollar"></i> Create Sales Handoff</button>
                </div>
            </form>
        </section>
    @endif

    <section class="commercial-split">
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Commercial Detail</h2></div>
            @include('commercial.partials.table', [
                'headers' => ['Field', 'Value'],
                'rows' => [
                    ['Estimated Value', e($opportunity->currency . ' ' . number_format((float) $opportunity->estimated_value, 2))],
                    ['Product / Service', e($opportunity->product_or_service ?: '-')],
                    ['Campaign', e($opportunity->campaign?->name ?: '-')],
                    ['Primary Stakeholder', e($opportunity->primaryStakeholder?->full_name ?: '-')],
                    ['Assigned Employee', e($opportunity->assignedEmployee?->name ?: '-')],
                    ['Expected Close', e($opportunity->expected_close_date?->format('M d, Y') ?: '-')],
                    ['Customer Need', e($opportunity->customer_need ?: '-')],
                    ['Problem Statement', e($opportunity->problem_statement ?: '-')],
                    ['Proposed Solution', e($opportunity->proposed_solution ?: '-')],
                ]
            ])
        </div>
        <div class="commercial-panel">
            <div class="commercial-panel-head"><h2>Stage History</h2></div>
            @include('commercial.partials.table', [
                'headers' => ['Stage', 'Reason', 'Date'],
                'rows' => $opportunity->stageHistory->map(fn ($history) => [
                    e($history->new_stage),
                    e($history->reason ?: '-'),
                    e($history->created_at?->format('M d, Y H:i') ?: '-'),
                ])->all()
            ])
        </div>
    </section>
</section>
@endsection
