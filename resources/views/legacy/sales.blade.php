<?php

$tenant_id = (int) onyx_tenant_id();
$today = date('Y-m-d');
$this_month = date('Y-m');

if (! function_exists('sales_h')) {
    function sales_h(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('sales_status_class')) {
    function sales_status_class(?string $status, float $balance = 0): string
    {
        $status = strtolower((string) $status);

        if ($status === 'paid' || $balance <= 0 && $status !== 'cancelled') {
            return 'ok';
        }

        if (in_array($status, ['overdue', 'cancelled'], true)) {
            return 'danger';
        }

        if (in_array($status, ['sent', 'draft'], true) || $balance > 0) {
            return 'warn';
        }

        return '';
    }
}

if (! function_exists('sales_badge')) {
    function sales_badge(string $label, string $class = ''): string
    {
        return '<span class="sales-badge ' . sales_h($class) . '">' . sales_h($label) . '</span>';
    }
}

if (! function_exists('sales_money_badge')) {
    function sales_money_badge(float $amount, string $currency, string $class = ''): string
    {
        return sales_badge(onyx_money($amount, $currency), $class);
    }
}

if (! function_exists('sales_document_actions')) {
    function sales_document_actions(array $row, string $type): string
    {
        $id = (int) $row['id'];
        $actions = [
            '<a class="sales-action" href="' . sales_h(onyx_legacy_url('sales_action.php?action=view_invoice&id=' . $id)) . '">View</a>',
        ];

        if ($type === 'quotation') {
            $actions[] = '<a class="sales-action" href="' . sales_h(onyx_legacy_url('sales_action.php?action=select_quotation&task=edit&id=' . $id)) . '">Edit</a>';
            $actions[] = '<a class="sales-action" href="' . sales_h(onyx_legacy_url('sales_action.php?action=select_quotation&task=approve&id=' . $id)) . '">Approve</a>';
            $actions[] = '<a class="sales-action primary" href="' . sales_h(onyx_legacy_url('sales_action.php?action=select_quotation&task=convert&id=' . $id)) . '">Convert</a>';
            $actions[] = '<a class="sales-action" href="' . sales_h(onyx_legacy_url('sales_action.php?action=select_quotation&task=print&id=' . $id)) . '">Print</a>';
        } else {
            $actions[] = '<a class="sales-action primary" href="' . sales_h(onyx_legacy_url('sales_action.php?action=capture_payment&id=' . $id)) . '">Pay</a>';
            $actions[] = '<a class="sales-action" target="_blank" href="' . sales_h(onyx_legacy_url('sales_action.php?action=view_invoice&id=' . $id . '&print=1')) . '">Print</a>';
        }

        return '<div class="sales-row-actions">' . implode('', $actions) . '</div>';
    }
}

if (! function_exists('sales_source_marker')) {
    function sales_source_marker(array $row): string
    {
        if (empty($row['commercial_reference'])) {
            return '';
        }

        $title = $row['commercial_title'] ?: 'Commercial opportunity';

        return '<span>Commercial: ' . sales_h($row['commercial_reference']) . ' / ' . sales_h($title) . '</span>';
    }
}

$quotation_rows = onyx_rows(
    'SELECT i.id, i.invoice_number, c.name AS customer_name, c.company_name, i.invoice_date, i.due_date, i.total, i.status,
            co.reference AS commercial_reference, co.title AS commercial_title, csh.status AS commercial_handoff_status
     FROM invoices i
     LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id
     LEFT JOIN commercial_opportunities co ON co.id = i.commercial_opportunity_id AND co.tenant_id = i.tenant_id
     LEFT JOIN commercial_sales_handoffs csh ON csh.id = i.commercial_handoff_id AND csh.tenant_id = i.tenant_id
     WHERE i.tenant_id = :tenant_id AND i.invoice_type = :type
     ORDER BY i.invoice_date DESC, i.id DESC',
    ['tenant_id' => $tenant_id, 'type' => 'quotation']
);

$invoice_rows = onyx_rows(
    'SELECT i.id, i.invoice_number, c.name AS customer_name, c.company_name, i.invoice_date, i.due_date,
            i.subtotal, i.tax, i.total, i.status, COALESCE(SUM(p.amount), 0) AS paid_amount,
            co.reference AS commercial_reference, co.title AS commercial_title, csh.status AS commercial_handoff_status
     FROM invoices i
     LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id
     LEFT JOIN invoice_payments p ON p.invoice_id = i.id AND p.tenant_id = i.tenant_id
     LEFT JOIN commercial_opportunities co ON co.id = i.commercial_opportunity_id AND co.tenant_id = i.tenant_id
     LEFT JOIN commercial_sales_handoffs csh ON csh.id = i.commercial_handoff_id AND csh.tenant_id = i.tenant_id
     WHERE i.tenant_id = :tenant_id AND i.invoice_type = :type
     GROUP BY i.id, i.invoice_number, c.name, c.company_name, i.invoice_date, i.due_date, i.subtotal, i.tax, i.total, i.status, co.reference, co.title, csh.status
     ORDER BY i.invoice_date DESC, i.id DESC',
    ['tenant_id' => $tenant_id, 'type' => 'invoice']
);

$payment_rows = onyx_rows(
    'SELECT p.id, i.invoice_number, c.name AS customer_name, p.payment_date, p.amount, p.method, p.reference
     FROM invoice_payments p
     JOIN invoices i ON i.id = p.invoice_id
     LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id
     WHERE p.tenant_id = :tenant_id
     ORDER BY p.payment_date DESC, p.id DESC',
    ['tenant_id' => $tenant_id]
);

$return_rows = onyx_rows(
    'SELECT i.id, i.invoice_number, c.name AS customer_name, i.invoice_date, i.total, i.status
     FROM invoices i
     LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id
     WHERE i.tenant_id = :tenant_id AND i.invoice_type IN ("return", "credit_note")
     ORDER BY i.invoice_date DESC, i.id DESC',
    ['tenant_id' => $tenant_id]
);

$daily_sales = (float) onyx_scalar(
    'SELECT COALESCE(SUM(total), 0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = :type AND invoice_date = :today AND status <> :cancelled',
    ['tenant_id' => $tenant_id, 'type' => 'invoice', 'today' => $today, 'cancelled' => 'cancelled']
);
$monthly_sales = (float) onyx_scalar(
    'SELECT COALESCE(SUM(total), 0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = :type AND invoice_date LIKE :month AND status <> :cancelled',
    ['tenant_id' => $tenant_id, 'type' => 'invoice', 'month' => $this_month . '%', 'cancelled' => 'cancelled']
);
$payments_today = (float) onyx_scalar(
    'SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE tenant_id = :tenant_id AND payment_date = :today',
    ['tenant_id' => $tenant_id, 'today' => $today]
);
$open_balance = (float) onyx_scalar(
    'SELECT COALESCE(SUM(i.total), 0) - COALESCE(SUM(paid.amount), 0)
     FROM invoices i
     LEFT JOIN (
        SELECT invoice_id, tenant_id, SUM(amount) AS amount
        FROM invoice_payments
        WHERE tenant_id = :tenant_id
        GROUP BY invoice_id, tenant_id
     ) paid ON paid.invoice_id = i.id AND paid.tenant_id = i.tenant_id
     WHERE i.tenant_id = :tenant_id_2 AND i.invoice_type = :type AND i.status <> :cancelled',
    ['tenant_id' => $tenant_id, 'tenant_id_2' => $tenant_id, 'type' => 'invoice', 'cancelled' => 'cancelled']
);
$overdue_count = (int) onyx_scalar(
    'SELECT COUNT(*) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = :type AND due_date < :today AND status NOT IN ("paid", "cancelled")',
    ['tenant_id' => $tenant_id, 'type' => 'invoice', 'today' => $today]
);
$pending_quotes = (int) onyx_scalar(
    'SELECT COUNT(*) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = :type AND status IN ("draft", "sent")',
    ['tenant_id' => $tenant_id, 'type' => 'quotation']
);
$best_products = onyx_rows(
    'SELECT p.name AS product_name, COALESCE(SUM(il.quantity), 0) AS qty
     FROM invoice_lines il
     JOIN invoices i ON i.id = il.invoice_id
     LEFT JOIN products p ON p.id = il.product_id
     WHERE i.tenant_id = :tenant_id AND i.invoice_type = :type AND i.status <> :cancelled
     GROUP BY p.id, p.name
     ORDER BY qty DESC LIMIT 5',
    ['tenant_id' => $tenant_id, 'type' => 'invoice', 'cancelled' => 'cancelled']
);

$context = onyx_page_start('Sales', 'Quotations, invoices, payments, returns, and sales performance in one workspace.');
$currency = $context['currency'];
$invoice_count = count($invoice_rows);
$quotation_count = count($quotation_rows);
$paid_invoice_count = count(array_filter($invoice_rows, static fn (array $row): bool => strtolower((string) $row['status']) === 'paid'));
$conversion_rate = $quotation_count > 0 ? round(($invoice_count / $quotation_count) * 100) : 0;
?>

<style>
    .sales-workspace,
    .sales-workspace * {
        border-radius: 0 !important;
    }

    .sales-workspace {
        display: grid;
        gap: 18px;
    }

    .sales-panel {
        background: var(--onyx-surface);
        border: 1px solid var(--onyx-border);
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
        padding: 18px;
    }

    .sales-toolbar,
    .sales-tabs,
    .sales-row-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .sales-toolbar {
        justify-content: space-between;
    }

    .sales-toolbar-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .sales-action,
    .sales-tab {
        align-items: center;
        background: rgba(255,255,255,0.035);
        border: 1px solid rgba(255,255,255,0.09);
        color: #fff;
        cursor: pointer;
        display: inline-flex;
        font: inherit;
        font-size: 0.64rem;
        font-weight: 800;
        gap: 8px;
        min-height: 36px;
        padding: 0 11px;
        text-decoration: none;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .sales-action.primary,
    .sales-tab.active {
        background: #fff;
        color: #050506;
    }

    .sales-action.danger {
        border-color: rgba(255,138,138,.35);
        color: #ff8a8a;
    }

    .sales-section-title {
        color: var(--onyx-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        line-height: 1.45;
        text-transform: uppercase;
    }

    .sales-summary-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(6, minmax(150px, 1fr));
    }

    .sales-metric {
        border: 1px solid rgba(255,255,255,0.08);
        min-width: 0;
        padding: 14px;
    }

    .sales-metric span {
        color: var(--onyx-muted);
        display: block;
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .sales-metric strong {
        color: #fff;
        display: block;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.25;
        margin-top: 8px;
        overflow-wrap: anywhere;
    }

    .sales-metric small {
        color: var(--onyx-muted);
        display: block;
        font-size: 0.62rem;
        font-weight: 700;
        margin-top: 6px;
    }

    .sales-tabs {
        background: rgba(0,0,0,.12);
        border: 1px solid rgba(255,255,255,.08);
        padding: 8px;
        position: sticky;
        top: 70px;
        z-index: 10;
    }

    .sales-tab-panel {
        display: none;
        gap: 16px;
    }

    .sales-tab-panel.active {
        display: grid;
    }

    .sales-table-wrap {
        margin-top: 14px;
        max-width: calc(100vw - 340px);
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 16px;
        scrollbar-color: rgba(255,255,255,0.28) rgba(255,255,255,0.06);
        scrollbar-width: thin;
        width: 100%;
    }

    .sales-table-wrap::-webkit-scrollbar {
        height: 10px;
    }

    .sales-table-wrap::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.06);
    }

    .sales-table-wrap::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.28);
    }

    .sales-table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 1180px;
    }

    .sales-table th,
    .sales-table td {
        border-bottom: 1px solid rgba(255,255,255,0.06);
        font-size: 0.68rem;
        padding: 9px;
        text-align: left;
        vertical-align: middle;
    }

    .sales-table th {
        color: var(--onyx-muted);
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .sales-table tbody tr:hover {
        background: rgba(255,255,255,0.045);
    }

    .sales-document strong,
    .sales-customer strong {
        color: #fff;
        display: block;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .sales-document span,
    .sales-customer span,
    .sales-muted {
        color: var(--onyx-muted);
        display: block;
        font-size: 0.62rem;
        margin-top: 3px;
    }

    .sales-badge {
        border: 1px solid rgba(255,255,255,0.12);
        color: #d8d8de;
        display: inline-flex;
        font-size: 0.56rem;
        font-weight: 800;
        padding: 5px 8px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .sales-badge.ok {
        color: #8ff0c3;
    }

    .sales-badge.warn {
        color: #ffd27a;
    }

    .sales-badge.danger {
        color: #ff8a8a;
    }

    .sales-report-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(180px, 1fr));
    }

    .sales-report-card {
        border: 1px solid rgba(255,255,255,0.08);
        padding: 14px;
    }

    .sales-report-card strong {
        color: #fff;
        display: block;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .sales-report-card span {
        color: var(--onyx-muted);
        display: block;
        font-size: 0.64rem;
        margin-top: 6px;
    }

    .sales-empty {
        border: 1px solid rgba(255,255,255,0.08);
        color: var(--onyx-muted);
        padding: 16px;
    }

    @media (max-width: 1180px) {
        .sales-summary-grid,
        .sales-report-grid {
            grid-template-columns: repeat(2, minmax(150px, 1fr));
        }
    }

    @media (max-width: 992px) {
        .sales-table-wrap {
            max-width: calc(100vw - 36px);
        }
        .sales-tabs {
            position: static;
        }
    }

    @media (max-width: 640px) {
        .sales-summary-grid,
        .sales-report-grid {
            grid-template-columns: 1fr;
        }
        .sales-toolbar {
            align-items: stretch;
            flex-direction: column;
        }
        .sales-toolbar-actions,
        .sales-action {
            justify-content: center;
            width: 100%;
        }
    }
</style>

<div class="sales-workspace">
    <section class="sales-panel">
        <div class="sales-toolbar">
            <div>
                <div class="sales-section-title">Sales Command Center</div>
                <div class="sales-muted">Create, approve, invoice, collect, and review without repeating the same controls in multiple panels.</div>
            </div>
            <div class="sales-toolbar-actions">
                <a class="sales-action primary" href="<?= sales_h(onyx_legacy_url('sales_action.php?action=create_invoice&invoice_type=quotation')) ?>"><i class="fa-solid fa-file-signature"></i> Quotation</a>
                <a class="sales-action primary" href="<?= sales_h(onyx_legacy_url('sales_action.php?action=create_invoice&invoice_type=invoice')) ?>"><i class="fa-solid fa-file-invoice"></i> Invoice</a>
                <a class="sales-action" href="<?= sales_h(onyx_legacy_url('sales_action.php?action=select_invoice&task=payment')) ?>"><i class="fa-solid fa-money-bill-transfer"></i> Payment</a>
                <a class="sales-action" href="<?= sales_h(onyx_legacy_url('pos.php')) ?>"><i class="fa-solid fa-cash-register"></i> POS Sale</a>
            </div>
        </div>
    </section>

    <section class="sales-summary-grid">
        <div class="sales-metric"><span>Today Sales</span><strong><?= sales_h(onyx_money($daily_sales, $currency)) ?></strong><small><?= sales_h($today) ?></small></div>
        <div class="sales-metric"><span>Month Sales</span><strong><?= sales_h(onyx_money($monthly_sales, $currency)) ?></strong><small><?= sales_h(date('M Y')) ?></small></div>
        <div class="sales-metric"><span>Open Balance</span><strong><?= sales_h(onyx_money(max(0, $open_balance), $currency)) ?></strong><small><?= $overdue_count ?> overdue document(s)</small></div>
        <div class="sales-metric"><span>Payments Today</span><strong><?= sales_h(onyx_money($payments_today, $currency)) ?></strong><small><?= count($payment_rows) ?> total payment record(s)</small></div>
        <div class="sales-metric"><span>Pending Quotes</span><strong><?= sales_h((string) $pending_quotes) ?></strong><small><?= $quotation_count ?> quotation document(s)</small></div>
        <div class="sales-metric"><span>Conversion</span><strong><?= sales_h((string) $conversion_rate) ?>%</strong><small><?= $paid_invoice_count ?> paid invoice(s)</small></div>
    </section>

    <div class="sales-tabs" data-sales-tabs>
        <button class="sales-tab active" type="button" data-tab="overview">Overview</button>
        <button class="sales-tab" type="button" data-tab="quotations">Quotations</button>
        <button class="sales-tab" type="button" data-tab="invoices">Invoices</button>
        <button class="sales-tab" type="button" data-tab="payments">Payments</button>
        <button class="sales-tab" type="button" data-tab="returns">Returns</button>
        <button class="sales-tab" type="button" data-tab="reports">Reports</button>
    </div>

    <div class="sales-tab-panel active" data-sales-tab-panel="overview">
        <section class="sales-panel">
            <div class="sales-section-title">Active Workflow</div>
            <div class="sales-report-grid" style="margin-top:14px;">
                <div class="sales-report-card"><strong>Quotation to Invoice</strong><span>Prepare quotes, approve them, then convert accepted work into invoices.</span></div>
                <div class="sales-report-card"><strong>Invoice Collection</strong><span>Track total, paid amount, balance, status, and overdue exposure from one list.</span></div>
                <div class="sales-report-card"><strong>Payment Capture</strong><span>Record cash, bank, mobile money, card, cheque, or reference-based payments.</span></div>
                <div class="sales-report-card"><strong>Customer Context</strong><span>Sales documents remain tied to customer profiles, statements, and financial lifecycle.</span></div>
            </div>
        </section>

        <section class="sales-panel">
            <div class="sales-section-title">Recent Documents</div>
            <div class="sales-table-wrap">
                <table class="sales-table">
                    <colgroup>
                        <col style="width:180px;"><col style="width:220px;"><col style="width:120px;"><col style="width:140px;"><col style="width:120px;"><col style="width:280px;">
                    </colgroup>
                    <thead><tr><th>Document</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php $recent_documents = array_slice(array_merge($quotation_rows, $invoice_rows), 0, 8); ?>
                        <?php if ($recent_documents === []): ?>
                            <tr><td colspan="6"><div class="sales-empty">No sales documents have been created yet.</div></td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_documents as $row): ?>
                                <?php $type = str_starts_with((string) ($row['invoice_number'] ?? ''), 'QT-') ? 'quotation' : 'invoice'; ?>
                                <tr>
                                    <td><div class="sales-document"><strong><?= sales_h($row['invoice_number'] ?: '-') ?></strong><span><?= sales_h(ucfirst($type)) ?></span><?= sales_source_marker($row) ?></div></td>
                                    <td><div class="sales-customer"><strong><?= sales_h($row['customer_name'] ?: '-') ?></strong><span><?= sales_h($row['company_name'] ?? 'No company') ?></span></div></td>
                                    <td><?= sales_h($row['invoice_date'] ?: '-') ?></td>
                                    <td><?= sales_money_badge((float) ($row['total'] ?? 0), $currency) ?></td>
                                    <td><?= sales_badge(ucwords(str_replace('_', ' ', (string) ($row['status'] ?: '-'))), sales_status_class($row['status'] ?? null)) ?></td>
                                    <td><?= sales_document_actions($row, $type) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="sales-tab-panel" data-sales-tab-panel="quotations">
        <section class="sales-panel">
            <div class="sales-toolbar">
                <div class="sales-section-title">Quotations</div>
                <a class="sales-action primary" href="<?= sales_h(onyx_legacy_url('sales_action.php?action=create_invoice&invoice_type=quotation')) ?>"><i class="fa-solid fa-plus"></i> New Quotation</a>
            </div>
            <div class="sales-table-wrap">
                <table class="sales-table">
                    <colgroup>
                        <col style="width:180px;"><col style="width:240px;"><col style="width:120px;"><col style="width:140px;"><col style="width:120px;"><col style="width:360px;">
                    </colgroup>
                    <thead><tr><th>Quotation</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if ($quotation_rows === []): ?>
                            <tr><td colspan="6"><div class="sales-empty">No quotations found.</div></td></tr>
                        <?php else: ?>
                            <?php foreach ($quotation_rows as $row): ?>
                                <tr>
                                    <td><div class="sales-document"><strong><?= sales_h($row['invoice_number'] ?: '-') ?></strong><span>Quotation</span><?= sales_source_marker($row) ?></div></td>
                                    <td><div class="sales-customer"><strong><?= sales_h($row['customer_name'] ?: '-') ?></strong><span><?= sales_h($row['company_name'] ?? 'No company') ?></span></div></td>
                                    <td><?= sales_h($row['invoice_date'] ?: '-') ?></td>
                                    <td><?= sales_money_badge((float) ($row['total'] ?? 0), $currency) ?></td>
                                    <td><?= sales_badge(ucwords(str_replace('_', ' ', (string) ($row['status'] ?: '-'))), sales_status_class($row['status'] ?? null)) ?></td>
                                    <td><?= sales_document_actions($row, 'quotation') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="sales-tab-panel" data-sales-tab-panel="invoices">
        <section class="sales-panel">
            <div class="sales-toolbar">
                <div class="sales-section-title">Invoices</div>
                <a class="sales-action primary" href="<?= sales_h(onyx_legacy_url('sales_action.php?action=create_invoice&invoice_type=invoice')) ?>"><i class="fa-solid fa-plus"></i> New Invoice</a>
            </div>
            <div class="sales-table-wrap">
                <table class="sales-table">
                    <colgroup>
                        <col style="width:180px;"><col style="width:220px;"><col style="width:110px;"><col style="width:110px;"><col style="width:130px;"><col style="width:130px;"><col style="width:130px;"><col style="width:120px;"><col style="width:260px;">
                    </colgroup>
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Due</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if ($invoice_rows === []): ?>
                            <tr><td colspan="9"><div class="sales-empty">No invoices found.</div></td></tr>
                        <?php else: ?>
                            <?php foreach ($invoice_rows as $row): ?>
                                <?php $balance = max(0, (float) ($row['total'] ?? 0) - (float) ($row['paid_amount'] ?? 0)); ?>
                                <tr>
                                    <td><div class="sales-document"><strong><?= sales_h($row['invoice_number'] ?: '-') ?></strong><span>Invoice</span><?= sales_source_marker($row) ?></div></td>
                                    <td><div class="sales-customer"><strong><?= sales_h($row['customer_name'] ?: '-') ?></strong><span><?= sales_h($row['company_name'] ?? 'No company') ?></span></div></td>
                                    <td><?= sales_h($row['invoice_date'] ?: '-') ?></td>
                                    <td><?= sales_h($row['due_date'] ?: '-') ?></td>
                                    <td><?= sales_money_badge((float) ($row['total'] ?? 0), $currency) ?></td>
                                    <td><?= sales_money_badge((float) ($row['paid_amount'] ?? 0), $currency, 'ok') ?></td>
                                    <td><?= sales_money_badge($balance, $currency, $balance > 0 ? 'warn' : 'ok') ?></td>
                                    <td><?= sales_badge(ucwords(str_replace('_', ' ', (string) ($row['status'] ?: '-'))), sales_status_class($row['status'] ?? null, $balance)) ?></td>
                                    <td><?= sales_document_actions($row, 'invoice') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="sales-tab-panel" data-sales-tab-panel="payments">
        <section class="sales-panel">
            <div class="sales-toolbar">
                <div class="sales-section-title">Payment Activity</div>
                <a class="sales-action primary" href="<?= sales_h(onyx_legacy_url('sales_action.php?action=select_invoice&task=payment')) ?>"><i class="fa-solid fa-plus"></i> Record Payment</a>
            </div>
            <div class="sales-table-wrap">
                <table class="sales-table">
                    <colgroup>
                        <col style="width:180px;"><col style="width:240px;"><col style="width:120px;"><col style="width:140px;"><col style="width:130px;"><col style="width:240px;">
                    </colgroup>
                    <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Method</th><th>Amount</th><th>Reference</th></tr></thead>
                    <tbody>
                        <?php if ($payment_rows === []): ?>
                            <tr><td colspan="6"><div class="sales-empty">No payments have been recorded yet.</div></td></tr>
                        <?php else: ?>
                            <?php foreach ($payment_rows as $row): ?>
                                <tr>
                                    <td><div class="sales-document"><strong><?= sales_h($row['invoice_number'] ?: '-') ?></strong><span>Payment</span></div></td>
                                    <td><?= sales_h($row['customer_name'] ?: '-') ?></td>
                                    <td><?= sales_h($row['payment_date'] ?: '-') ?></td>
                                    <td><?= sales_badge(ucwords(str_replace('_', ' ', (string) ($row['method'] ?: '-')))) ?></td>
                                    <td><?= sales_money_badge((float) ($row['amount'] ?? 0), $currency, 'ok') ?></td>
                                    <td><?= sales_h($row['reference'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="sales-tab-panel" data-sales-tab-panel="returns">
        <section class="sales-panel">
            <div class="sales-toolbar">
                <div>
                    <div class="sales-section-title">Returns & Credit Notes</div>
                    <div class="sales-muted">Use this area for reversed sales, credit notes, and product return tracking.</div>
                </div>
                <a class="sales-action" href="<?= sales_h(onyx_legacy_url('sales_action.php?action=create_invoice&invoice_type=credit_note')) ?>"><i class="fa-solid fa-rotate-left"></i> Credit Note</a>
            </div>
            <div class="sales-table-wrap">
                <table class="sales-table">
                    <colgroup>
                        <col style="width:180px;"><col style="width:240px;"><col style="width:120px;"><col style="width:140px;"><col style="width:120px;"><col style="width:260px;">
                    </colgroup>
                    <thead><tr><th>Document</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if ($return_rows === []): ?>
                            <tr><td colspan="6"><div class="sales-empty">No returns or credit notes found.</div></td></tr>
                        <?php else: ?>
                            <?php foreach ($return_rows as $row): ?>
                                <tr>
                                    <td><div class="sales-document"><strong><?= sales_h($row['invoice_number'] ?: '-') ?></strong><span>Return / Credit</span></div></td>
                                    <td><?= sales_h($row['customer_name'] ?: '-') ?></td>
                                    <td><?= sales_h($row['invoice_date'] ?: '-') ?></td>
                                    <td><?= sales_money_badge((float) ($row['total'] ?? 0), $currency) ?></td>
                                    <td><?= sales_badge(ucwords(str_replace('_', ' ', (string) ($row['status'] ?: '-'))), sales_status_class($row['status'] ?? null)) ?></td>
                                    <td><div class="sales-row-actions"><a class="sales-action" href="<?= sales_h(onyx_legacy_url('sales_action.php?action=view_invoice&id=' . (int) $row['id'])) ?>">View</a></div></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="sales-tab-panel" data-sales-tab-panel="reports">
        <section class="sales-panel">
            <div class="sales-section-title">Sales Intelligence</div>
            <div class="sales-report-grid" style="margin-top:14px;">
                <div class="sales-report-card"><strong><?= sales_h(onyx_money($monthly_sales, $currency)) ?></strong><span>Monthly sales</span></div>
                <div class="sales-report-card"><strong><?= sales_h(onyx_money($daily_sales * 365, $currency)) ?></strong><span>Annualized run rate</span></div>
                <div class="sales-report-card"><strong><?= sales_h(onyx_money($monthly_sales * 0.3, $currency)) ?></strong><span>Estimated gross profit</span></div>
                <div class="sales-report-card"><strong><?= sales_h($best_products === [] ? 'No data' : ($best_products[0]['product_name'] ?? 'No data')) ?></strong><span>Best selling product</span></div>
            </div>
        </section>
    </div>
</div>

<script>
    document.querySelectorAll('[data-sales-tabs] .sales-tab').forEach(function (button) {
        button.addEventListener('click', function () {
            const tab = button.getAttribute('data-tab');
            document.querySelectorAll('[data-sales-tabs] .sales-tab').forEach(function (item) {
                item.classList.toggle('active', item === button);
            });
            document.querySelectorAll('[data-sales-tab-panel]').forEach(function (panel) {
                panel.classList.toggle('active', panel.getAttribute('data-sales-tab-panel') === tab);
            });
        });
    });
</script>

<?php onyx_page_end(); ?>
