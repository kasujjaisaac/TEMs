<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . onyx_legacy_url('payroll.php?success=' . urlencode('Payroll run prepared for review.')));
    exit;
}

$context = onyx_page_start('Payroll', 'Payroll runs, earnings, deductions, statutory items, payslips, approvals, and payment files.');
$currency = $context['currency'];

$payrollRows = [
    ['EMP-001', 'Super Admin', onyx_money(0, $currency), onyx_money(0, $currency), onyx_money(0, $currency), 'Draft'],
    ['EMP-002', 'Cashier', onyx_money(0, $currency), onyx_money(0, $currency), onyx_money(0, $currency), 'Draft'],
    ['EMP-003', 'Store Manager', onyx_money(0, $currency), onyx_money(0, $currency), onyx_money(0, $currency), 'Draft'],
];
$deductions = [
    ['PAYE', 'Income tax', 'Statutory', 'Configured'],
    ['NSSF', 'Social security', 'Statutory', 'Configured'],
    ['Advance Recovery', 'Salary advance deduction', 'Internal', 'Available'],
    ['Loan Recovery', 'Staff loan deduction', 'Internal', 'Available'],
];
$payrollChecks = [
    ['Employee master', 'Basic pay and payment method confirmed'],
    ['Attendance import', 'Absence, overtime, and lateness included'],
    ['Allowances', 'Transport, housing, commission, bonus'],
    ['Deductions', 'PAYE, NSSF, advances, loans, penalties'],
    ['Approval workflow', 'Prepared, reviewed, approved, paid'],
    ['Payslips', 'Employee payslip generation and delivery'],
    ['Payment file', 'Bank/mobile money payout export'],
    ['Accounting posting', 'Payroll journal to finance'],
];
?>

<div class="ops-board">
    <?php if (! empty($_GET['success'])): ?><div class="ops-card" style="color:#8ff0c3;"><?= htmlspecialchars((string) $_GET['success']) ?></div><?php endif; ?>
    <div class="ops-strip">
        <div class="ops-card"><span>Payroll Period</span><strong><?= htmlspecialchars(date('F Y')) ?></strong></div>
        <div class="ops-card"><span>Gross Pay</span><strong><?= htmlspecialchars(onyx_money(0, $currency)) ?></strong></div>
        <div class="ops-card"><span>Deductions</span><strong><?= htmlspecialchars(onyx_money(0, $currency)) ?></strong></div>
        <div class="ops-card"><span>Net Pay</span><strong><?= htmlspecialchars(onyx_money(0, $currency)) ?></strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Payroll Run Setup', 'fa-money-check-dollar', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Period</label><input type="month" value="<?= htmlspecialchars(date('Y-m')) ?>"></div>
                <div class="ops-field"><label>Run Type</label><select><option>Regular payroll</option><option>Bonus payroll</option><option>Final payroll</option><option>Correction run</option></select></div>
                <div class="ops-field"><label>Department</label><select><option>All departments</option><option>Administration</option><option>Sales</option><option>Inventory</option><option>Finance</option></select></div>
                <div class="ops-field"><label>Payment Method</label><select><option>Bank transfer</option><option>Mobile money</option><option>Cash</option></select></div>
                <div class="ops-field full"><label>Payroll Notes</label><textarea rows="2" placeholder="Allowances, deductions, overtime, approval notes"></textarea></div>
                <button class="ops-btn" type="submit">Prepare Payroll</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Payroll Worksheet', 'fa-table-list', 'span-8'); ?>
            <?php onyx_table(['Code', 'Employee', 'Gross', 'Deductions', 'Net Pay', 'Status'], $payrollRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Payroll Controls', 'fa-shield-halved', 'span-4'); ?>
            <div class="ops-checks">
                <?php foreach ($payrollChecks as $check): ?>
                    <div class="ops-check"><strong><?= htmlspecialchars($check[0]) ?></strong><span><?= htmlspecialchars($check[1]) ?></span></div>
                <?php endforeach; ?>
            </div>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Deduction and Statutory Register', 'fa-scale-balanced', 'span-12'); ?>
            <?php onyx_table(['Item', 'Description', 'Type', 'Status'], $deductions); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
