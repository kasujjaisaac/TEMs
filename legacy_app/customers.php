<?php
require_once __DIR__ . '/includes/erp_layout.php';

$pdo = onyx_db();

function ensure_customer_columns(PDO $pdo): void
{
    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM customers')->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[] = $column['Field'];
    }

    if (!in_array('company_name', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN company_name VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('contact_person', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN contact_person VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('customer_group', $columns, true)) {
        $pdo->exec("ALTER TABLE customers ADD COLUMN customer_group VARCHAR(50) DEFAULT 'retail'");
    }
}

ensure_customer_columns($pdo);

$context = onyx_page_start('Customers', 'Complete customer account management with profiles, finances, equipment, and maintenance.');
$currency = $context['currency'];
$tenant_id = (int) (onyx_tenant_id() ?? 0);
$customers = array_map(
    static fn (array $row): array => [
        $row['customer_code'] ?: '-',
        $row['name'],
        $row['company_name'] ?: '-',
        $row['phone'] ?: '-',
        $row['email'] ?: '-',
        onyx_money((float) $row['credit_limit'], $currency),
        onyx_money((float) $row['credit_balance'], $currency),
        ((int) $row['is_active']) === 1 ? 'Active' : 'Inactive',
    ],
    onyx_rows(
        'SELECT customer_code, name, company_name, phone, email, credit_limit, credit_balance, is_active
         FROM customers
         WHERE tenant_id = :tenant_id
         ORDER BY name ASC',
        ['tenant_id' => $tenant_id]
    )
);

$customer_options = onyx_rows(
    'SELECT id, name FROM customers WHERE tenant_id = :tenant_id ORDER BY name ASC',
    ['tenant_id' => $tenant_id]
);
?>

<div class="module-grid">
    <?php onyx_panel_start('Customer Actions', 'fa-user-gear', 'span-12'); ?>
        <?php onyx_action_grid([
            ['label' => 'Add Customer', 'icon' => 'fa-user-plus', 'href' => 'customers_action.php?action=add'],
            ['label' => 'Create Quotation', 'icon' => 'fa-file-invoice', 'href' => 'sales_action.php?action=create_invoice&invoice_type=quotation'],
            ['label' => 'Receive Payment', 'icon' => 'fa-money-bill', 'href' => 'sales_action.php?action=select_invoice&task=payment'],
            ['label' => 'Schedule Maintenance', 'icon' => 'fa-calendar-check', 'href' => 'customers_action.php?action=maintenance'],
            ['label' => 'Print Statement', 'icon' => 'fa-print', 'href' => 'customers_action.php?action=print'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Customer List', 'fa-users', 'span-12'); ?>
        <div style="margin-bottom:12px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <label class="muted" for="selected_customer">Select Customer:</label>
            <select id="selected_customer" style="padding:8px; min-width:220px;">
                <option value="">-- choose customer --</option>
                <?php foreach ($customer_options as $opt): ?>
                    <option value="<?= htmlspecialchars((string) $opt['id']) ?>"><?= htmlspecialchars((string) $opt['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <a id="btnEdit" class="action-btn" href="#">Edit</a>
            <a id="btnView" class="action-btn" href="#">View Account</a>
            <a id="btnPrint" class="action-btn" href="#">Print</a>
            <a id="btnDelete" class="action-btn" href="#">Delete</a>
        </div>
        <?php onyx_table(
            ['Customer Code', 'Name', 'Company', 'Phone', 'Email', 'Credit Limit', 'Balance', 'Status'],
            $customers
        ); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Customer Account Hub', 'fa-id-badge', 'span-12'); ?>
        <div class="profile-grid">
            <div class="profile-card"><strong>Customer Profile</strong><span>Customer code, name, company, contact person, phone, email, address, TIN, registration date, and account status.</span></div>
            <div class="profile-card"><strong>Financial Information</strong><span>Credit limit, current balance, outstanding invoices, payment history, customer statements, and ledgers.</span></div>
            <div class="profile-card"><strong>Equipment Installed</strong><span>CCTV, NVR/DVR, solar panels, inverters, batteries, routers, switches, access control systems, and biometric devices.</span></div>
            <div class="profile-card"><strong>Customer Actions</strong><span>Create quotations, receive payments, schedule maintenance, print statements, and view purchase history.</span></div>
        </div>
    <?php onyx_panel_end(); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const sel = document.getElementById('selected_customer');
    const btnEdit = document.getElementById('btnEdit');
    const btnView = document.getElementById('btnView');
    const btnPrint = document.getElementById('btnPrint');
    const btnDelete = document.getElementById('btnDelete');

    function updateButtons(){
        const id = sel.value;
        const base = 'customers_action.php';
        const disabledStyle = 'opacity:0.6; pointer-events:none;';
        if (!id) {
            btnEdit.setAttribute('style', disabledStyle);
            btnView.setAttribute('style', disabledStyle);
            btnPrint.setAttribute('style', disabledStyle);
            btnDelete.setAttribute('style', disabledStyle);
            btnEdit.href = '#'; btnView.href = '#'; btnPrint.href = '#'; btnDelete.href = '#';
            return;
        }
        btnEdit.style = '';
        btnView.style = '';
        btnPrint.style = '';
        btnDelete.style = '';
        btnEdit.href = base + '?action=edit&id=' + encodeURIComponent(id);
        btnView.href = base + '?action=view&id=' + encodeURIComponent(id);
        btnPrint.href = base + '?action=print&id=' + encodeURIComponent(id);
        btnDelete.href = base + '?action=delete&id=' + encodeURIComponent(id);
        btnDelete.onclick = function(){ return confirm('Delete the selected customer?'); };
    }

    sel.addEventListener('change', updateButtons);
    updateButtons();
});
</script>

<?php onyx_page_end(); ?>
