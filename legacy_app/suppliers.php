<?php
require_once __DIR__ . '/includes/erp_layout.php';

$context = onyx_page_start('Suppliers', 'Supplier management for purchases, balances, payments, and statements.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();
$suppliers = array_map(
    static fn (array $row): array => [
        $row['supplier_code'],
        $row['company_name'],
        $row['contact_person'] ?: '-',
        $row['phone'] ?: '-',
        $row['email'] ?: '-',
        $row['address'] ?: '-',
        $row['tin_number'] ?: '-',
        onyx_money(0, $currency),
    ],
    onyx_rows(
        'SELECT supplier_code, company_name, contact_person, phone, email, address, tin_number
         FROM suppliers
         WHERE tenant_id = :tenant_id
         ORDER BY company_name ASC',
        ['tenant_id' => $tenant_id]
    )
);
?>

<div class="module-grid">
    <?php onyx_panel_start('Supplier Actions', 'fa-truck-field', 'span-12'); ?>
        <?php onyx_action_grid([
            ['label' => 'Add Supplier', 'icon' => 'fa-plus', 'href' => 'suppliers_action.php?action=add'],
            ['label' => 'Edit Supplier', 'icon' => 'fa-pen-to-square', 'href' => 'suppliers_action.php?action=edit'],
            ['label' => 'Purchase History', 'icon' => 'fa-cart-shopping', 'href' => 'suppliers_action.php?action=view'],
            ['label' => 'Supplier Payments', 'icon' => 'fa-money-check-dollar', 'href' => 'suppliers_action.php?action=payments'],
            ['label' => 'Supplier Statements', 'icon' => 'fa-file-lines', 'href' => 'suppliers_action.php?action=statement'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Supplier Management', 'fa-industry', 'span-12'); ?>
        <?php
        $supplier_options = onyx_rows('SELECT id, company_name FROM suppliers WHERE tenant_id = :tenant_id ORDER BY company_name ASC', ['tenant_id' => $tenant_id]);
        ?>
        <div style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
            <label class="muted" for="selected_supplier">Select Supplier:</label>
            <select id="selected_supplier" style="padding:8px; min-width:260px;">
                <option value="">-- choose supplier --</option>
                <?php foreach ($supplier_options as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['id']) ?>"><?= htmlspecialchars($opt['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <a id="supEdit" class="action-btn" href="#">Edit</a>
            <a id="supDelete" class="action-btn" href="#">Delete</a>
            <a id="supView" class="action-btn" href="#">View</a>
            <a id="supPrint" class="action-btn" href="#">Statement</a>
        </div>

        <?php onyx_table(
            ['Supplier ID', 'Supplier Name', 'Contact Person', 'Phone', 'Email', 'Address', 'Tax Number', 'Outstanding Balance'],
            $suppliers
        ); ?>
    <?php onyx_panel_end(); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const sel = document.getElementById('selected_supplier');
    const btnEdit = document.getElementById('supEdit');
    const btnDelete = document.getElementById('supDelete');
    const btnView = document.getElementById('supView');
    const btnPrint = document.getElementById('supPrint');

    function updateButtons(){
        const id = sel.value;
        const base = 'suppliers_action.php';
        const disabledStyle = 'opacity:0.6; pointer-events:none;';
        if (!id) {
            btnEdit.setAttribute('style', disabledStyle);
            btnDelete.setAttribute('style', disabledStyle);
            btnView.setAttribute('style', disabledStyle);
            btnPrint.setAttribute('style', disabledStyle);
            btnEdit.href = '#'; btnDelete.href = '#'; btnView.href = '#'; btnPrint.href = '#';
            return;
        }
        btnEdit.style = '';
        btnDelete.style = '';
        btnView.style = '';
        btnPrint.style = '';
        btnEdit.href = base + '?action=edit&id=' + encodeURIComponent(id);
        btnDelete.href = base + '?action=delete&id=' + encodeURIComponent(id);
        btnView.href = base + '?action=view&id=' + encodeURIComponent(id);
        btnPrint.href = base + '?action=statement&id=' + encodeURIComponent(id);
    }

    sel.addEventListener('change', updateButtons);
    updateButtons();
});
</script>

<?php onyx_page_end(); ?>
