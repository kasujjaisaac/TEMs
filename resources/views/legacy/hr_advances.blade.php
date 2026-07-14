<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . onyx_legacy_url('hr_advances.php?success=' . urlencode('Advance or loan request submitted for approval.')));
    exit;
}

$context = onyx_page_start('Advances & Loans', 'Salary advances, staff loans, approval workflow, repayment schedules, and payroll recovery.');
$currency = $context['currency'];

$advanceRows = [
    ['ADV-' . date('Ym') . '-001', 'Cashier', onyx_money(250000, $currency), '3 months', 'Pending'],
    ['ADV-' . date('Ym') . '-002', 'Store Manager', onyx_money(0, $currency), '1 month', 'Draft'],
];
$scheduleRows = [
    ['Cashier', onyx_money(250000, $currency), onyx_money(83333, $currency), date('M Y'), 'Pending payroll'],
];
?>

<div class="ops-board">
    <?php if (! empty($_GET['success'])): ?><div class="ops-card" style="color:#8ff0c3;"><?= htmlspecialchars((string) $_GET['success']) ?></div><?php endif; ?>
    <div class="ops-strip">
        <div class="ops-card"><span>Open Requests</span><strong>2</strong></div>
        <div class="ops-card"><span>Outstanding</span><strong><?= htmlspecialchars(onyx_money(250000, $currency)) ?></strong></div>
        <div class="ops-card"><span>Payroll Recoveries</span><strong>1</strong></div>
        <div class="ops-card"><span>Overdue</span><strong>0</strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Advance / Loan Request', 'fa-hand-holding-dollar', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee</label><select><option>Cashier</option><option>Store Manager</option><option>Super Admin</option></select></div>
                <div class="ops-field"><label>Request Type</label><select><option>Salary advance</option><option>Staff loan</option><option>Emergency support</option></select></div>
                <div class="ops-field"><label>Amount</label><input type="number" step="0.01" placeholder="0.00"></div>
                <div class="ops-field"><label>Recovery Months</label><input type="number" min="1" placeholder="1"></div>
                <div class="ops-field"><label>First Recovery</label><input type="month" value="<?= htmlspecialchars(date('Y-m')) ?>"></div>
                <div class="ops-field"><label>Approver</label><input type="text" placeholder="Finance / HR"></div>
                <div class="ops-field wide"><label>Purpose</label><input type="text" placeholder="Reason for request"></div>
                <button class="ops-btn" type="submit">Submit Request</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Advance Register', 'fa-list', 'span-8'); ?>
            <?php onyx_table(['Reference', 'Employee', 'Amount', 'Recovery', 'Status'], $advanceRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Recovery Schedule', 'fa-money-bill-transfer', 'span-4'); ?>
            <?php onyx_table(['Employee', 'Balance', 'Monthly', 'Next Period', 'Status'], $scheduleRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
