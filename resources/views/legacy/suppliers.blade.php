<?php

if (! function_exists('supplier_register_h')) {
    function supplier_register_h(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('supplier_register_badge')) {
    function supplier_register_badge(string $label, string $class = ''): string
    {
        return '<span class="supplier-badge ' . supplier_register_h($class) . '">' . supplier_register_h($label) . '</span>';
    }
}

$context = onyx_page_start('Suppliers', 'Supplier accounts, terms, balances, payments, statements, and purchasing readiness.');
$currency = $context['currency'];
$tenant_id = (int) (onyx_tenant_id() ?? 0);
$pdo = onyx_db();

if (function_exists('supplier_ensure_schema')) {
    supplier_ensure_schema($pdo);
} else {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS supplier_payments (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) NOT NULL,
            supplier_id BIGINT(20) NOT NULL,
            payment_date DATE NOT NULL,
            amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            method VARCHAR(80) DEFAULT NULL,
            reference VARCHAR(155) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (Throwable) {
    }
}

$suppliers = onyx_rows(
    'SELECT s.*,
            COALESCE(p.payment_total, 0) AS payment_total,
            COALESCE(p.payment_count, 0) AS payment_count
     FROM suppliers s
     LEFT JOIN (
        SELECT supplier_id, tenant_id, SUM(amount) AS payment_total, COUNT(*) AS payment_count
        FROM supplier_payments
        WHERE tenant_id = :tenant_id_payments
        GROUP BY supplier_id, tenant_id
     ) p ON p.supplier_id = s.id AND p.tenant_id = s.tenant_id
     WHERE s.tenant_id = :tenant_id
     ORDER BY s.company_name ASC',
    ['tenant_id_payments' => $tenant_id, 'tenant_id' => $tenant_id]
);

$total_balance = 0.0;
$active_count = 0;
$watch_count = 0;
$payment_total = 0.0;
foreach ($suppliers as $supplier) {
    $total_balance += (float) ($supplier['credit_balance'] ?? 0);
    $payment_total += (float) ($supplier['payment_total'] ?? 0);
    if (in_array(($supplier['status'] ?? 'active'), ['active', 'preferred'], true)) $active_count++;
    if (in_array(($supplier['status'] ?? ''), ['watchlist', 'blocked'], true) || ($supplier['rating'] ?? '') === 'risk') $watch_count++;
}
$message = $_GET['success'] ?? $_GET['error'] ?? '';
$message_type = isset($_GET['error']) ? 'error' : 'success';
?>

<style>
    .supplier-register,.supplier-register *{border-radius:0!important}.supplier-register{display:grid;gap:18px}.supplier-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:18px;overflow:hidden}.supplier-toolbar,.supplier-actions,.supplier-filters{align-items:center;display:flex;flex-wrap:wrap;gap:8px}.supplier-toolbar{justify-content:space-between}.supplier-title{color:var(--onyx-muted);font-size:11px;font-weight:900;letter-spacing:.8px;text-transform:uppercase}.supplier-muted{color:var(--onyx-muted);display:block;font-size:10px;margin-top:4px}.supplier-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;display:inline-flex;font-size:10px;font-weight:900;gap:8px;min-height:38px;padding:0 12px;text-decoration:none;text-transform:uppercase}.supplier-btn.primary{background:#fff;color:#050506}.supplier-btn.danger{color:#ff8a8a}.supplier-kpis{display:grid;gap:10px;grid-template-columns:repeat(5,minmax(0,1fr))}.supplier-kpi{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:14px}.supplier-kpi span{color:var(--onyx-muted);display:block;font-size:9px;font-weight:900;text-transform:uppercase}.supplier-kpi strong{color:#fff;display:block;font-size:16px;margin-top:8px;word-break:break-word}.supplier-field{display:grid;gap:6px;min-width:150px}.supplier-field.search{flex:1 1 280px}.supplier-field label{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.supplier-field input,.supplier-field select{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-size:10px;min-height:38px;padding:0 10px}.supplier-field select option{background:#050506;color:#fff}.supplier-table-wrap{margin-top:14px;max-width:calc(100vw - 340px);overflow-x:auto;padding-bottom:14px}.supplier-table{border-collapse:collapse;table-layout:fixed;width:1420px}.supplier-table th,.supplier-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:9px;text-align:left;vertical-align:top}.supplier-table th{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.supplier-name strong{color:#fff;display:block;font-size:11px}.supplier-name span{color:var(--onyx-muted);display:block;font-size:9px;margin-top:3px}.supplier-badge{border:1px solid rgba(255,255,255,.12);display:inline-flex;font-size:9px;font-weight:900;padding:4px 7px;text-transform:uppercase}.supplier-badge.ok{color:#8ff0c3}.supplier-badge.warn{color:#ffd27a}.supplier-badge.danger{color:#ff8a8a}.supplier-empty,.supplier-alert{border:1px solid rgba(255,255,255,.08);color:var(--onyx-muted);padding:14px}.supplier-alert{border-color:rgba(143,240,195,.24);color:#8ff0c3;font-size:11px;font-weight:800}.supplier-alert.error{border-color:rgba(255,138,138,.28);color:#ff8a8a}@media(max-width:1180px){.supplier-kpis{grid-template-columns:repeat(2,1fr)}.supplier-table-wrap{max-width:calc(100vw - 36px)}}@media(max-width:680px){.supplier-kpis{grid-template-columns:1fr}.supplier-btn{justify-content:center;width:100%}}
</style>

<div class="supplier-register">
    <?php if ($message !== ''): ?><section class="supplier-panel"><div class="supplier-alert <?= $message_type === 'error' ? 'error' : '' ?>"><?= supplier_register_h($message) ?></div></section><?php endif; ?>

    <section class="supplier-panel">
        <div class="supplier-toolbar">
            <div><div class="supplier-title">Supplier Command Center</div><span class="supplier-muted">Manage purchasing partners, commercial terms, balances, settlement records, and supplier risk.</span></div>
            <div class="supplier-actions">
                <a class="supplier-btn primary" href="<?= supplier_register_h(onyx_legacy_url('suppliers_action.php?action=add')) ?>"><i class="fa-solid fa-plus"></i> Add Supplier</a>
                <a class="supplier-btn" href="<?= supplier_register_h(onyx_legacy_url('suppliers_action.php?action=payments')) ?>"><i class="fa-solid fa-money-check-dollar"></i> Supplier Payment</a>
                <a class="supplier-btn" href="<?= supplier_register_h(onyx_legacy_url('suppliers_action.php?action=statement')) ?>"><i class="fa-solid fa-file-lines"></i> Statement</a>
                <a class="supplier-btn" href="<?= supplier_register_h(onyx_legacy_url('purchases.php')) ?>"><i class="fa-solid fa-cart-shopping"></i> Purchases</a>
            </div>
        </div>
    </section>

    <section class="supplier-kpis">
        <div class="supplier-kpi"><span>Total Suppliers</span><strong><?= supplier_register_h(count($suppliers)) ?></strong></div>
        <div class="supplier-kpi"><span>Active / Preferred</span><strong><?= supplier_register_h($active_count) ?></strong></div>
        <div class="supplier-kpi"><span>Watch Risk</span><strong><?= supplier_register_h($watch_count) ?></strong></div>
        <div class="supplier-kpi"><span>Outstanding Balance</span><strong><?= supplier_register_h(onyx_money($total_balance, $currency)) ?></strong></div>
        <div class="supplier-kpi"><span>Payments Recorded</span><strong><?= supplier_register_h(onyx_money($payment_total, $currency)) ?></strong></div>
    </section>

    <section class="supplier-panel">
        <div class="supplier-title">Supplier Filters</div>
        <div class="supplier-filters" style="margin-top:12px;">
            <div class="supplier-field search"><label>Search</label><input id="supplier-search" type="search" placeholder="Search supplier, code, phone, email, TIN"></div>
            <div class="supplier-field"><label>Status</label><select id="supplier-status"><option value="">All status</option><option value="active">Active</option><option value="preferred">Preferred</option><option value="watchlist">Watchlist</option><option value="blocked">Blocked</option></select></div>
            <div class="supplier-field"><label>Rating</label><select id="supplier-rating"><option value="">All ratings</option><option value="excellent">Excellent</option><option value="approved">Approved</option><option value="average">Average</option><option value="risk">Risk</option><option value="blocked">Blocked</option></select></div>
        </div>
    </section>

    <section class="supplier-panel">
        <div class="supplier-title">Supplier Register</div>
        <div class="supplier-table-wrap">
            <table class="supplier-table">
                <colgroup><col style="width:210px"><col style="width:120px"><col style="width:150px"><col style="width:180px"><col style="width:150px"><col style="width:120px"><col style="width:120px"><col style="width:150px"><col style="width:130px"><col style="width:290px"></colgroup>
                <thead><tr><th>Supplier</th><th>Type</th><th>Contact</th><th>Email</th><th>Phone</th><th>Status</th><th>Terms</th><th>Balance</th><th>Lead Time</th><th>Actions</th></tr></thead>
                <tbody id="supplier-body">
                    <?php if ($suppliers === []): ?><tr><td colspan="10"><div class="supplier-empty">No suppliers registered yet.</div></td></tr><?php endif; ?>
                    <?php foreach ($suppliers as $supplier): ?>
                        <?php
                            $status = (string) ($supplier['status'] ?? 'active');
                            $rating = (string) ($supplier['rating'] ?? 'approved');
                            $badgeClass = in_array($status, ['blocked', 'watchlist'], true) || $rating === 'risk' ? 'danger' : ($status === 'preferred' || $rating === 'excellent' ? 'ok' : 'warn');
                            $search = strtolower(trim(implode(' ', [$supplier['supplier_code'] ?? '', $supplier['company_name'] ?? '', $supplier['contact_person'] ?? '', $supplier['phone'] ?? '', $supplier['email'] ?? '', $supplier['tin_number'] ?? ''])));
                        ?>
                        <tr data-supplier-row="1" data-search="<?= supplier_register_h($search) ?>" data-status="<?= supplier_register_h($status) ?>" data-rating="<?= supplier_register_h($rating) ?>">
                            <td><div class="supplier-name"><strong><?= supplier_register_h($supplier['company_name']) ?></strong><span><?= supplier_register_h(($supplier['supplier_code'] ?: '-') . ' / TIN ' . ($supplier['tin_number'] ?: '-')) ?></span></div></td>
                            <td><?= supplier_register_h($supplier['supplier_type'] ?? 'goods') ?></td>
                            <td><?= supplier_register_h($supplier['contact_person'] ?: '-') ?></td>
                            <td><?= supplier_register_h($supplier['email'] ?: '-') ?></td>
                            <td><?= supplier_register_h($supplier['phone'] ?: '-') ?></td>
                            <td><?= supplier_register_badge($status, $badgeClass) ?><span class="supplier-muted"><?= supplier_register_h($rating) ?></span></td>
                            <td><?= supplier_register_h($supplier['payment_terms'] ?? '-') ?><span class="supplier-muted"><?= supplier_register_h($supplier['preferred_payment_method'] ?: 'No method') ?></span></td>
                            <td><?= supplier_register_h(onyx_money((float) ($supplier['credit_balance'] ?? 0), $currency)) ?><span class="supplier-muted">Limit <?= supplier_register_h(onyx_money((float) ($supplier['credit_limit'] ?? 0), $currency)) ?></span></td>
                            <td><?= supplier_register_h((int) ($supplier['lead_time_days'] ?? 0)) ?> days</td>
                            <td><div class="supplier-actions"><a class="supplier-btn" href="<?= supplier_register_h(onyx_legacy_url('suppliers_action.php?action=view&id=' . (int) $supplier['id'])) ?>">View</a><a class="supplier-btn" href="<?= supplier_register_h(onyx_legacy_url('suppliers_action.php?action=edit&id=' . (int) $supplier['id'])) ?>">Edit</a><a class="supplier-btn" href="<?= supplier_register_h(onyx_legacy_url('suppliers_action.php?action=payments&id=' . (int) $supplier['id'])) ?>">Pay</a><a class="supplier-btn" href="<?= supplier_register_h(onyx_legacy_url('suppliers_action.php?action=statement&id=' . (int) $supplier['id'])) ?>">Statement</a></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('supplier-search');
    const status = document.getElementById('supplier-status');
    const rating = document.getElementById('supplier-rating');
    const rows = Array.from(document.querySelectorAll('[data-supplier-row="1"]'));
    function applyFilters() {
        const q = (search.value || '').toLowerCase().trim();
        rows.forEach(function (row) {
            const show = (!q || row.dataset.search.includes(q)) && (!status.value || row.dataset.status === status.value) && (!rating.value || row.dataset.rating === rating.value);
            row.hidden = !show;
        });
    }
    [search, status, rating].forEach(function (input) {
        input.addEventListener('input', applyFilters);
        input.addEventListener('change', applyFilters);
    });
});
</script>

<?php onyx_page_end(); ?>
