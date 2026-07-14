<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . onyx_legacy_url('banking.php?success=' . urlencode('Bank transaction captured for review.')));
    exit;
}

$context = onyx_page_start('Banking', 'Bank accounts, cash book, deposits, transfers, withdrawals, and reconciliation.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();

$paymentsToday = (float) onyx_scalar('SELECT COALESCE(SUM(amount),0) FROM invoice_payments WHERE tenant_id = :tenant_id AND payment_date = :today', ['tenant_id' => $tenant_id, 'today' => date('Y-m-d')]);
$supplierBalances = (float) onyx_scalar('SELECT COALESCE(SUM(credit_balance),0) FROM suppliers WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);
$bankAccounts = [
    ['Main Operating Account', 'Bank', 'UGX', onyx_money($paymentsToday, $currency), 'Reconcile'],
    ['Cash Till', 'Cash', 'UGX', onyx_money(0, $currency), 'Count due'],
    ['Mobile Money Collection', 'Mobile money', 'UGX', onyx_money(0, $currency), 'Review'],
    ['Supplier Payment Wallet', 'Payments', 'UGX', onyx_money($supplierBalances, $currency), 'Controlled'],
];
$transactions = [
    [date('Y-m-d'), 'Customer receipt batch', 'Deposit', onyx_money($paymentsToday, $currency), 'Unmatched'],
    [date('Y-m-d'), 'Supplier settlement queue', 'Payment', onyx_money($supplierBalances, $currency), 'Pending approval'],
    [date('Y-m-d'), 'Cash count posting', 'Cash count', onyx_money(0, $currency), 'Draft'],
];
?>

<div class="ops-board">
    <?php if (! empty($_GET['success'])): ?><div class="ops-card" style="color:#8ff0c3;"><?= htmlspecialchars((string) $_GET['success']) ?></div><?php endif; ?>
    <div class="ops-actions">
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('settings.php?section=finance')) ?>"><i class="fa-solid fa-building-columns"></i><span>Add Account</span></a>
        <a class="ops-action" href="#bank-transaction"><i class="fa-solid fa-money-bill-transfer"></i><span>Bank Transfer</span></a>
        <a class="ops-action" href="#bank-transaction"><i class="fa-solid fa-arrow-down"></i><span>Deposit</span></a>
        <a class="ops-action" href="#bank-transaction"><i class="fa-solid fa-arrow-up"></i><span>Withdrawal</span></a>
        <a class="ops-action" href="#bank-queue"><i class="fa-solid fa-scale-balanced"></i><span>Reconcile</span></a>
        <a class="ops-action" href="#bank-queue"><i class="fa-solid fa-file-export"></i><span>Statement Import</span></a>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Bank and Cash Accounts', 'fa-vault', 'span-12'); ?>
            <?php onyx_table(['Account', 'Type', 'Currency', 'Book Balance', 'Status'], $bankAccounts); ?>
        <?php onyx_panel_end(); ?>

        <div id="bank-transaction"></div>
        <?php onyx_panel_start('Transaction Capture', 'fa-money-bill-transfer', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Date</label><input type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></div>
                <div class="ops-field"><label>Account</label><select><option>Main Operating Account</option><option>Cash Till</option><option>Mobile Money Collection</option></select></div>
                <div class="ops-field"><label>Type</label><select><option>Deposit</option><option>Withdrawal</option><option>Transfer</option><option>Bank charge</option></select></div>
                <div class="ops-field"><label>Amount</label><input type="number" step="0.01" placeholder="0.00"></div>
                <div class="ops-field full"><label>Description</label><textarea rows="2" placeholder="Narration, reference, cheque number, mobile money transaction id"></textarea></div>
                <button class="ops-btn" type="submit">Record Transaction</button>
            </form>
        <?php onyx_panel_end(); ?>

        <div id="bank-queue"></div>
        <?php onyx_panel_start('Cash Book and Matching Queue', 'fa-list-check', 'span-12'); ?>
            <?php onyx_table(['Date', 'Description', 'Type', 'Amount', 'Match Status'], $transactions); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
