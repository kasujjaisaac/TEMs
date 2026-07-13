<?php
$context = onyx_page_start('Payroll Readiness', 'Basic pay, allowances, deductions, bank details, attendance imports, approval status, and payroll handoff.');
$currency = $context['currency'];

$readinessRows = [
    ['EMP-001', 'Super Admin', onyx_money(0, $currency), 'Bank ready', 'Ready'],
    ['EMP-002', 'Cashier', onyx_money(0, $currency), 'Attendance pending', 'Review'],
    ['EMP-003', 'Store Manager', onyx_money(0, $currency), 'Bank pending', 'Blocked'],
];
$payItems = [
    ['Transport allowance', 'Allowance', onyx_money(0, $currency), 'Optional'],
    ['Overtime', 'Earning', onyx_money(0, $currency), 'Attendance driven'],
    ['PAYE', 'Deduction', onyx_money(0, $currency), 'Statutory'],
    ['NSSF', 'Deduction', onyx_money(0, $currency), 'Statutory'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Payroll Ready</span><strong>1</strong></div>
        <div class="ops-card"><span>Needs Review</span><strong>1</strong></div>
        <div class="ops-card"><span>Blocked</span><strong>1</strong></div>
        <div class="ops-card"><span>Pay Items</span><strong><?= htmlspecialchars((string) count($payItems)) ?></strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Payroll Readiness Check', 'fa-clipboard-check', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee</label><select><option>Super Admin</option><option>Cashier</option><option>Store Manager</option></select></div>
                <div class="ops-field"><label>Basic Pay</label><input type="number" step="0.01" placeholder="0.00"></div>
                <div class="ops-field"><label>Payment Method</label><select><option>Bank transfer</option><option>Mobile money</option><option>Cash</option></select></div>
                <div class="ops-field"><label>Bank / Wallet</label><input type="text" placeholder="Account or wallet"></div>
                <div class="ops-field"><label>Attendance Imported</label><select><option>Yes</option><option>No</option><option>Not required</option></select></div>
                <div class="ops-field"><label>Approvals</label><select><option>Ready</option><option>Review</option><option>Blocked</option></select></div>
                <div class="ops-field wide"><label>Payroll Note</label><input type="text" placeholder="Missing details or approval notes"></div>
                <button class="ops-btn" type="button">Mark Ready</button>
                <a class="ops-btn ghost" href="<?= htmlspecialchars(onyx_legacy_url('payroll.php')) ?>">Open Payroll</a>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Employee Payroll Readiness', 'fa-users-gear', 'span-8'); ?>
            <?php onyx_table(['Code', 'Employee', 'Basic Pay', 'Check', 'Status'], $readinessRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Pay Item Register', 'fa-scale-balanced', 'span-4'); ?>
            <?php onyx_table(['Item', 'Type', 'Amount', 'Rule'], $payItems); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
