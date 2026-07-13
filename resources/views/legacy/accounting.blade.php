<?php
$context = onyx_page_start('Accounting', 'General ledger, chart of accounts, journals, tax control, receivables, payables, and period close.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();

$customersDue = (float) onyx_scalar('SELECT COALESCE(SUM(credit_balance),0) FROM customers WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);
$suppliersDue = (float) onyx_scalar('SELECT COALESCE(SUM(credit_balance),0) FROM suppliers WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);
$inventoryValue = (float) onyx_scalar('SELECT COALESCE(SUM(current_stock * buying_price),0) FROM products WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);
$monthSales = (float) onyx_scalar('SELECT COALESCE(SUM(total),0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = "invoice" AND invoice_date BETWEEN :start AND :end', ['tenant_id' => $tenant_id, 'start' => date('Y-m-01'), 'end' => date('Y-m-t')]);

$accounts = [
    ['1000', 'Cash and Bank', 'Asset', 'Current asset', onyx_money(0, $currency)],
    ['1100', 'Accounts Receivable', 'Asset', 'Customer balances', onyx_money($customersDue, $currency)],
    ['1200', 'Inventory', 'Asset', 'Stock valuation', onyx_money($inventoryValue, $currency)],
    ['2000', 'Accounts Payable', 'Liability', 'Supplier balances', onyx_money($suppliersDue, $currency)],
    ['4000', 'Sales Revenue', 'Income', 'Invoice revenue', onyx_money($monthSales, $currency)],
    ['5000', 'Cost of Goods Sold', 'Expense', 'Inventory cost movement', onyx_money(0, $currency)],
];

$journalRows = [
    ['JV-' . date('Ym') . '-001', date('Y-m-d'), 'Sales posting', 'Sales Revenue', onyx_money($monthSales, $currency), 'Draft'],
    ['JV-' . date('Ym') . '-002', date('Y-m-d'), 'Receivables control', 'Accounts Receivable', onyx_money($customersDue, $currency), 'Review'],
    ['JV-' . date('Ym') . '-003', date('Y-m-d'), 'Supplier control', 'Accounts Payable', onyx_money($suppliersDue, $currency), 'Review'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Monthly Revenue</span><strong><?= htmlspecialchars(onyx_money($monthSales, $currency)) ?></strong></div>
        <div class="ops-card"><span>Receivables</span><strong><?= htmlspecialchars(onyx_money($customersDue, $currency)) ?></strong></div>
        <div class="ops-card"><span>Payables</span><strong><?= htmlspecialchars(onyx_money($suppliersDue, $currency)) ?></strong></div>
        <div class="ops-card"><span>Inventory Asset</span><strong><?= htmlspecialchars(onyx_money($inventoryValue, $currency)) ?></strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Chart of Accounts', 'fa-sitemap', 'span-12'); ?>
            <?php onyx_table(['Code', 'Account', 'Class', 'Control', 'Balance'], $accounts); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Journal Entry Capture', 'fa-pen-to-square', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Date</label><input type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></div>
                <div class="ops-field"><label>Reference</label><input type="text" placeholder="JV reference"></div>
                <div class="ops-field"><label>Debit Account</label><select><option>Cash and Bank</option><option>Accounts Receivable</option><option>Inventory</option></select></div>
                <div class="ops-field"><label>Credit Account</label><select><option>Sales Revenue</option><option>Accounts Payable</option><option>Tax Payable</option></select></div>
                <div class="ops-field"><label>Debit</label><input type="number" step="0.01" placeholder="0.00"></div>
                <div class="ops-field"><label>Credit</label><input type="number" step="0.01" placeholder="0.00"></div>
                <div class="ops-field wide"><label>Cost Center</label><input type="text" placeholder="Branch, department, project"></div>
                <div class="ops-field full"><label>Narration</label><textarea rows="2" placeholder="Journal narration and approval note"></textarea></div>
                <button class="ops-btn" type="button">Post Journal</button>
                <button class="ops-btn ghost" type="button">Save Draft</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Recent Journal Queue', 'fa-book', 'span-8'); ?>
            <?php onyx_table(['Reference', 'Date', 'Narration', 'Account', 'Amount', 'Status'], $journalRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Accounting Controls', 'fa-shield-halved', 'span-4'); ?>
            <div class="ops-tags">
                <span class="ops-tag">Trial balance</span>
                <span class="ops-tag">General ledger</span>
                <span class="ops-tag">Tax payable</span>
                <span class="ops-tag">Receivables ageing</span>
                <span class="ops-tag">Payables ageing</span>
                <span class="ops-tag">Period close</span>
                <span class="ops-tag">Audit trail</span>
            </div>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
