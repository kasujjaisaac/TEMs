<?php

$tenant_id = (int) onyx_tenant_id();
$pdo = onyx_db();
$today = date('Y-m-d');
$this_month = date('Y-m');

$quotation_rows = [];
$invoice_rows = [];
$payment_rows = [];
try {
    $quotation_rows = onyx_rows(
        'SELECT i.id, i.invoice_number, c.name AS customer_name, i.invoice_date, i.total, i.status
         FROM invoices i
         LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id
         WHERE i.tenant_id = :tenant_id AND i.invoice_type = :type
         ORDER BY i.invoice_date DESC, i.id DESC',
        ['tenant_id' => $tenant_id, 'type' => 'quotation']
    );

    $invoice_rows = onyx_rows(
        'SELECT i.id, i.invoice_number, c.name AS customer_name, i.invoice_date, i.total, i.tax, i.status, COALESCE(SUM(p.amount), 0) AS paid_amount
         FROM invoices i
         LEFT JOIN customers c ON c.id = i.customer_id AND c.tenant_id = i.tenant_id
         LEFT JOIN invoice_payments p ON p.invoice_id = i.id AND p.tenant_id = i.tenant_id
         WHERE i.tenant_id = :tenant_id AND i.invoice_type = :type
         GROUP BY i.id, i.invoice_number, c.name, i.invoice_date, i.total, i.tax, i.status
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
} catch (PDOException $e) {
    $quotation_rows = [];
    $invoice_rows = [];
    $payment_rows = [];
}

$daily_sales = (float) onyx_scalar(
    'SELECT COALESCE(SUM(total), 0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = :type AND invoice_date = :today AND status <> :cancelled',
    ['tenant_id' => $tenant_id, 'type' => 'invoice', 'today' => $today, 'cancelled' => 'cancelled']
);
$monthly_sales = (float) onyx_scalar(
    'SELECT COALESCE(SUM(total), 0) FROM invoices WHERE tenant_id = :tenant_id AND invoice_type = :type AND invoice_date LIKE :month AND status <> :cancelled',
    ['tenant_id' => $tenant_id, 'type' => 'invoice', 'month' => $this_month . '%', 'cancelled' => 'cancelled']
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

$context = onyx_page_start('Sales', 'Support for quotations, sales orders, invoices, payments, returns, and reports.');
$currency = $context['currency'];
?>

<div class="module-grid">
    <?php onyx_panel_start('Sales Workflow', 'fa-file-invoice', 'span-12'); ?>
        <?php onyx_action_grid([
            ['label' => 'Create Quotation', 'icon' => 'fa-file-signature', 'href' => 'sales_action.php?action=create_invoice&invoice_type=quotation'],
            ['label' => 'Edit Quotation', 'icon' => 'fa-pen-to-square', 'href' => 'sales_action.php?action=select_quotation&task=edit'],
            ['label' => 'Approve Quotation', 'icon' => 'fa-check-double', 'href' => 'sales_action.php?action=select_quotation&task=approve'],
            ['label' => 'Convert to Invoice', 'icon' => 'fa-file-circle-plus', 'href' => 'sales_action.php?action=select_quotation&task=convert'],
            ['label' => 'Print Quotation', 'icon' => 'fa-file-pdf', 'href' => 'sales_action.php?action=select_quotation&task=print'],
            ['label' => 'Email Quotation', 'icon' => 'fa-envelope', 'href' => 'sales_action.php?action=select_quotation&task=email'],
            ['label' => 'Sales Orders', 'icon' => 'fa-clipboard-list', 'href' => 'sales_action.php?action=create_invoice&invoice_type=invoice'],
            ['label' => 'Receive Payment', 'icon' => 'fa-money-bill-transfer', 'href' => 'sales_action.php?action=select_invoice&task=payment'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Sales Orders & Services', 'fa-clipboard-list', 'span-12'); ?>
        <?php onyx_clean_list([
            ['Product Sales', 'Enabled'],
            ['Service Sales', 'Enabled'],
            ['Installation Charges', 'Enabled'],
            ['Transport Charges', 'Enabled'],
            ['Discounts', 'Enabled'],
            ['VAT', 'Enabled'],
            ['Delivery Charges', 'Enabled'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Invoice Statuses', 'fa-list-check', 'span-12'); ?>
        <?php onyx_clean_list([
            ['Draft', 'Available'],
            ['Approved', 'Available'],
            ['Paid', 'Available'],
            ['Partial Payment', 'Available'],
            ['Unpaid', 'Available'],
            ['Cancelled', 'Available'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Payment Methods', 'fa-credit-card', 'span-12'); ?>
        <?php onyx_clean_list([
            ['Cash', 'Enabled'],
            ['Bank Transfer', 'Enabled'],
            ['Mobile Money', 'Enabled'],
            ['Card', 'Enabled'],
            ['Cheque', 'Enabled'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Sales Returns', 'fa-rotate-left', 'span-12'); ?>
        <?php onyx_clean_list([
            ['Product Returns', 'Enabled'],
            ['Service Adjustments', 'Enabled'],
            ['Credit Notes', 'Enabled'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Quotation List', 'fa-file-signature', 'span-12'); ?>
        <?php onyx_table_html(
            ['Quotation No', 'Customer', 'Date', 'Total', 'Status', 'Actions'],
            array_map(static fn (array $row): array => [
                $row['invoice_number'] ?: '-',
                $row['customer_name'] ?: '-',
                $row['invoice_date'] ?: '-',
                onyx_money((float) ($row['total'] ?? 0), $currency),
                $row['status'] ?: '-',
                ['raw' => true, 'value' =>
                    '<a class="action-btn" href="sales_action.php?action=view_invoice&id=' . htmlspecialchars($row['id']) . '">View</a>' .
                    ' <a class="action-btn" href="sales_action.php?action=select_quotation&task=print&id=' . htmlspecialchars($row['id']) . '">Print</a>' .
                    ' <a class="action-btn" href="sales_action.php?action=select_quotation&task=edit&id=' . htmlspecialchars($row['id']) . '">Edit</a>' .
                    ' <a class="action-btn" href="sales_action.php?action=select_quotation&task=approve&id=' . htmlspecialchars($row['id']) . '">Approve</a>' .
                    ' <a class="action-btn" href="sales_action.php?action=select_quotation&task=convert&id=' . htmlspecialchars($row['id']) . '">Convert</a>'
                ],
            ], $quotation_rows)
        ); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Invoice List', 'fa-file-invoice', 'span-12'); ?>
        <?php onyx_table_html(
            ['Invoice No', 'Customer', 'Date', 'Total', 'Paid', 'Balance', 'Status', 'Actions'],
            array_map(static fn (array $row): array => [
                $row['invoice_number'] ?: '-',
                $row['customer_name'] ?: '-',
                $row['invoice_date'] ?: '-',
                onyx_money((float) ($row['total'] ?? 0), $currency),
                onyx_money((float) ($row['paid_amount'] ?? 0), $currency),
                onyx_money((float) (($row['total'] ?? 0) - ($row['paid_amount'] ?? 0)), $currency),
                $row['status'] ?: '-',
                ['raw' => true, 'value' =>
                    '<a class="action-btn" href="sales_action.php?action=view_invoice&id=' . htmlspecialchars($row['id']) . '">View</a>' .
                    ' <a class="action-btn" href="sales_action.php?action=capture_payment&id=' . htmlspecialchars($row['id']) . '">Pay</a>' .
                    ' <a class="action-btn" href="sales_action.php?action=view_invoice&id=' . htmlspecialchars($row['id']) . '&print=1" target="_blank">Print</a>'
                ],
            ], $invoice_rows)
        ); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Payment Activity', 'fa-money-bill-transfer', 'span-12'); ?>
        <?php onyx_table(
            ['Invoice No', 'Customer', 'Date', 'Method', 'Amount', 'Reference'],
            array_map(static fn (array $row): array => [
                $row['invoice_number'] ?: '-',
                $row['customer_name'] ?: '-',
                $row['payment_date'] ?: '-',
                $row['method'] ?: '-',
                onyx_money((float) ($row['amount'] ?? 0), $currency),
                $row['reference'] ?: '-',
            ], $payment_rows)
        ); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Reports', 'fa-chart-simple', 'span-12'); ?>
        <?php onyx_clean_list([
            ['Sales by Customer', 'Available'],
            ['Sales by Product', 'Available'],
            ['Sales by Technician', 'Available'],
            ['Sales by Branch', 'Available'],
            ['Monthly Sales', onyx_money($monthly_sales, $currency ?? 'UGX')],
            ['Annual Sales', onyx_money($daily_sales * 365, $currency ?? 'UGX')],
            ['Gross Profit', onyx_money(($monthly_sales * 0.3), $currency ?? 'UGX')],
            ['Best Selling Products', $best_products === [] ? 'No data' : $best_products[0]['product_name'] ?? 'No data'],
        ]); ?>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
