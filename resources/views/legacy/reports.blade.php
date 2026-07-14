<?php
$context = onyx_page_start('Reports', 'Financial statements, operational reports, statutory exports, schedules, and management packs.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();

$reportRows = [
    ['Profit and Loss', 'Revenue, COGS, expenses, margin', 'Monthly', 'Finance'],
    ['Balance Sheet', 'Assets, liabilities, equity', 'Monthly', 'Finance'],
    ['Cash Flow Statement', 'Receipts, payments, cash movement', 'Monthly', 'Finance'],
    ['Trial Balance', 'Ledger control report', 'On demand', 'Accounting'],
    ['Receivables Ageing', 'Customer balances and collection risk', 'Weekly', 'Sales/Finance'],
    ['Payables Ageing', 'Supplier balances and payment plan', 'Weekly', 'Procurement/Finance'],
    ['Inventory Valuation', 'Stock value and reorder exposure', 'Daily', 'Inventory'],
    ['Payroll Summary', 'Salary costs, deductions, net pay', 'Monthly', 'HR/Finance'],
    ['Tax Report', 'VAT, withholding, statutory deductions', 'Monthly', 'Finance'],
];
$scheduledRows = [
    ['Management Pack', 'Every Monday', 'Directors', 'Active'],
    ['Receivables Follow-up', 'Daily', 'Finance Manager', 'Active'],
    ['Stock Valuation', 'Month end', 'Store Manager', 'Draft'],
];
$selectedReport = $_GET['report_type'] ?? '';
?>

<div class="ops-board">
    <?php if ($selectedReport !== ''): ?><div class="ops-card" style="color:#8ff0c3;">Generated <?= htmlspecialchars((string) $selectedReport) ?> preview for the selected period.</div><?php endif; ?>
    <div class="ops-report-grid">
        <div class="ops-report-card"><i class="fa-solid fa-chart-line"></i><strong>Financial Statements</strong><span>P&L, balance sheet, cash flow</span></div>
        <div class="ops-report-card"><i class="fa-solid fa-users"></i><strong>Customer Reports</strong><span>Ageing, sales, statements</span></div>
        <div class="ops-report-card"><i class="fa-solid fa-boxes-stacked"></i><strong>Inventory Reports</strong><span>Valuation, reorder, movement</span></div>
        <div class="ops-report-card"><i class="fa-solid fa-file-export"></i><strong>Exports</strong><span>PDF, Excel, statutory files</span></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Report Builder', 'fa-sliders', 'span-12'); ?>
            <form class="ops-filters" method="get">
                <select name="report_type"><option>Profit and Loss</option><option>Balance Sheet</option><option>Cash Flow</option><option>Payroll Summary</option><option>Tax Report</option></select>
                <input name="date_from" type="date" value="<?= htmlspecialchars($_GET['date_from'] ?? date('Y-m-01')) ?>">
                <input name="date_to" type="date" value="<?= htmlspecialchars($_GET['date_to'] ?? date('Y-m-t')) ?>">
                <select name="branch"><option>All branches</option><option>Main branch</option><option>Warehouse</option></select>
                <button class="ops-btn" type="submit">Generate</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Report Catalogue', 'fa-folder-open', 'span-8'); ?>
            <?php onyx_table(['Report', 'Purpose', 'Frequency', 'Owner'], $reportRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Scheduled Reports', 'fa-clock', 'span-4'); ?>
            <?php onyx_table(['Report', 'Schedule', 'Recipient', 'Status'], $scheduledRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
