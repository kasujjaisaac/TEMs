<?php
$context = onyx_page_start('Budgets', 'Department budgets, approvals, forecasts, controls, and variance monitoring.');
$currency = $context['currency'];

$budgetRows = [
    ['Sales', 'Revenue target', onyx_money(25000000, $currency), onyx_money(0, $currency), '0%', 'Open'],
    ['Inventory', 'Stock purchases', onyx_money(12000000, $currency), onyx_money(0, $currency), '0%', 'Controlled'],
    ['Operations', 'Utilities and logistics', onyx_money(4500000, $currency), onyx_money(0, $currency), '0%', 'Draft'],
    ['HR', 'Payroll and benefits', onyx_money(8000000, $currency), onyx_money(0, $currency), '0%', 'Pending'],
];
$approvalRows = [
    ['BUD-' . date('Y') . '-001', 'Inventory restock', onyx_money(3500000, $currency), 'Store Manager', 'Finance review'],
    ['BUD-' . date('Y') . '-002', 'Staff training', onyx_money(1200000, $currency), 'HR', 'Draft'],
    ['BUD-' . date('Y') . '-003', 'Sales campaign', onyx_money(1800000, $currency), 'Sales', 'Director approval'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Annual Budget</span><strong><?= htmlspecialchars(onyx_money(49500000, $currency)) ?></strong></div>
        <div class="ops-card"><span>Committed</span><strong><?= htmlspecialchars(onyx_money(0, $currency)) ?></strong></div>
        <div class="ops-card"><span>Available</span><strong><?= htmlspecialchars(onyx_money(49500000, $currency)) ?></strong></div>
        <div class="ops-card"><span>Variance Alerts</span><strong>0</strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Budget Planning Form', 'fa-clipboard-list', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Department</label><select><option>Sales</option><option>Inventory</option><option>Operations</option><option>HR</option><option>Finance</option></select></div>
                <div class="ops-field"><label>Budget Type</label><select><option>Revenue target</option><option>Expense limit</option><option>Capital expenditure</option><option>Project budget</option></select></div>
                <div class="ops-field"><label>Period</label><select><option>Monthly</option><option>Quarterly</option><option>Annual</option></select></div>
                <div class="ops-field"><label>Amount</label><input type="number" step="0.01" placeholder="0.00"></div>
                <div class="ops-field full"><label>Purpose and control notes</label><textarea rows="2" placeholder="Budget justification, limits, approval notes"></textarea></div>
                <button class="ops-btn" type="button">Submit Budget</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Department Budget Monitor', 'fa-chart-simple', 'span-8'); ?>
            <?php onyx_table(['Department', 'Budget', 'Approved', 'Actual', 'Used', 'Status'], $budgetRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Approval Queue', 'fa-user-check', 'span-4'); ?>
            <?php onyx_table(['Ref', 'Request', 'Amount', 'Owner', 'Status'], $approvalRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
