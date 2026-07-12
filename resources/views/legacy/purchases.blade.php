<?php

$context = onyx_page_start('Purchases', 'Purchase orders, supplier invoices, and payment tracking.');
$tenant_id = onyx_tenant_id();

// Require view permission
if (!has_permission('manage_purchases') && !has_permission('view_reports')) {
    // allow read-only listing to users with view_reports or manage_purchases
    // otherwise block
    require_permission('manage_purchases');
}

// Ensure purchases table exists (simple schema)
$pdo = onyx_db();
$create_sql = "CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    supplier VARCHAR(255) DEFAULT '',
    purchase_date DATE DEFAULT NULL,
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4";
$pdo->exec($create_sql);

// Handle create purchase POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    require_permission('manage_purchases');
    $supplier = $_POST['supplier'] ?? '';
    $date = $_POST['purchase_date'] ?? null;
    $total = $_POST['total_amount'] ?? '0';
    $notes = $_POST['notes'] ?? null;

    $stmt = $pdo->prepare('INSERT INTO purchases (tenant_id, supplier, purchase_date, total_amount, notes) VALUES (:tenant_id, :supplier, :purchase_date, :total_amount, :notes)');
    $stmt->execute([
        ':tenant_id' => $tenant_id,
        ':supplier' => $supplier,
        ':purchase_date' => $date ?: null,
        ':total_amount' => $total,
        ':notes' => $notes,
    ]);

    header('Location: purchases.php?created=1');
    exit();
}

// Fetch purchases for tenant
$stmt = $pdo->prepare('SELECT id, supplier, purchase_date, total_amount, notes, created_at FROM purchases WHERE tenant_id = :tenant_id ORDER BY created_at DESC');
$stmt->execute([':tenant_id' => $tenant_id]);
$purchases = $stmt->fetchAll();
?>

<div class="module-grid">
    <?php onyx_panel_start('Purchases', 'fa-shopping-cart', 'span-12'); ?>
        <p class="muted">Manage purchase orders and supplier invoices for this tenant.</p>

        <?php if (isset($_GET['created'])): ?>
            <div style="background: rgba(40,167,69,0.12); padding:10px; border-radius:8px; color:#b8f3c2; margin-bottom:12px;">Purchase created successfully.</div>
        <?php endif; ?>

        <div style="margin-bottom:18px; display:flex; gap:10px; align-items:center;">
            <?php if (has_permission('manage_purchases')): ?>
                <button id="toggleNew" class="action-btn">New Purchase</button>
            <?php endif; ?>
        </div>

        <div id="newForm" style="display:none; margin-bottom:18px;">
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div style="display:grid; gap:10px; grid-template-columns: repeat(2, 1fr);">
                    <div>
                        <label class="muted">Supplier</label>
                        <input name="supplier" type="text" required style="width:100%; padding:8px; border-radius:6px;">
                    </div>
                    <div>
                        <label class="muted">Purchase Date</label>
                        <input name="purchase_date" type="date" style="width:100%; padding:8px; border-radius:6px;">
                    </div>
                    <div>
                        <label class="muted">Total Amount</label>
                        <input name="total_amount" type="number" step="0.01" required style="width:100%; padding:8px; border-radius:6px;">
                    </div>
                    <div>
                        <label class="muted">Notes</label>
                        <input name="notes" type="text" style="width:100%; padding:8px; border-radius:6px;">
                    </div>
                </div>
                <div style="margin-top:8px;"><button type="submit" class="action-btn">Save Purchase</button></div>
            </form>
        </div>

        <div style="margin-top:12px;">
            <?php if (empty($purchases)): ?>
                <div class="muted">No purchases found for this tenant.</div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Supplier</th>
                            <th>Purchase Date</th>
                            <th>Total</th>
                            <th>Notes</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['supplier']) ?></td>
                                <td><?= htmlspecialchars($row['purchase_date']) ?></td>
                                <td><?= htmlspecialchars($row['total_amount']) ?></td>
                                <td><?= htmlspecialchars($row['notes']) ?></td>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const t = document.getElementById('toggleNew');
    const f = document.getElementById('newForm');
    if (t && f) {
        t.addEventListener('click', function(){
            f.style.display = f.style.display === 'none' ? 'block' : 'none';
        });
    }
});
</script>
