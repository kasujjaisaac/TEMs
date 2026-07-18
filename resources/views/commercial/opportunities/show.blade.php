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

    <section class="commercial-grid">
        <div class="commercial-card"><span>Organization</span><strong>{{ $opportunity->organization?->legal_name }}</strong></div>
        <div class="commercial-card"><span>Stage</span><strong>{{ $opportunity->current_stage }}</strong></div>
        <div class="commercial-card"><span>Probability</span><strong>{{ $opportunity->probability }}%</strong></div>
        <div class="commercial-card"><span>Weighted Value</span><strong>{{ $opportunity->currency }} {{ number_format($opportunity->weighted_value, 2) }}</strong></div>
    </section>

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
