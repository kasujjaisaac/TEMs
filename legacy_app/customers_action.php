<?php
require_once __DIR__ . '/includes/erp_layout.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$tenant_id = (int) (onyx_tenant_id() ?? 0);
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

function redirect_back($msg = '', $success = true) {
    $q = '';
    if ($msg !== '') $q = ($success ? '?success=' : '?error=') . urlencode($msg);
    header('Location: customers.php' . $q);
    exit();
}

function ensure_customer_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_equipment (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        customer_id BIGINT(20) NOT NULL,
        equipment_type VARCHAR(155) NOT NULL,
        model VARCHAR(155) DEFAULT NULL,
        serial_number VARCHAR(100) DEFAULT NULL,
        installation_date DATE DEFAULT NULL,
        warranty_expiry DATE DEFAULT NULL,
        assigned_technician VARCHAR(155) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_customer_equipment_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_maintenance (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        customer_id BIGINT(20) NOT NULL,
        title VARCHAR(155) NOT NULL,
        scheduled_on DATE DEFAULT NULL,
        status VARCHAR(30) DEFAULT 'scheduled',
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_customer_maintenance_tenant (tenant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

ensure_customer_columns($pdo);
ensure_customer_tables($pdo);

if (!$action) {
    redirect_back('No action specified.', false);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'add') {
        $context = onyx_page_start('Add Customer', 'Create a new customer profile.');
        ?>
        <form method="POST" action="customers_action.php">
            <input type="hidden" name="action" value="add">
            <div class="panel span-8">
                <div class="panel-title"><i class="fa-solid fa-user-plus"></i> Add Customer</div>
                <div style="margin-top:12px;">
                    <label>Customer Code</label><br>
                    <input type="text" name="customer_code" placeholder="Auto-generated if blank" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Name</label><br>
                    <input type="text" name="name" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Company Name</label><br>
                    <input type="text" name="company_name" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Contact Person</label><br>
                    <input type="text" name="contact_person" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Phone</label><br>
                    <input type="text" name="phone" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Email</label><br>
                    <input type="email" name="email" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Address</label><br>
                    <textarea name="address" style="width:100%;padding:8px;margin:6px 0;"></textarea>
                    <label>City</label><br>
                    <input type="text" name="city" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Country</label><br>
                    <input type="text" name="country" style="width:100%;padding:8px;margin:6px 0;">
                    <label>TIN</label><br>
                    <input type="text" name="tin_number" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Customer Group</label><br>
                    <select name="customer_group" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="corporate">Corporate</option>
                        <option value="individual">Individual</option>
                    </select>
                    <label>Credit Limit</label><br>
                    <input type="number" step="0.01" name="credit_limit" value="0.00" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Current Balance</label><br>
                    <input type="number" step="0.01" name="credit_balance" value="0.00" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Status</label><br>
                    <select name="is_active" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <button class="action-btn" type="submit" style="margin-top:8px;">Save Customer</button>
                </div>
            </div>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'edit') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for edit.', false);
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Customer not found.', false);
        $context = onyx_page_start('Edit Customer', 'Update customer details.');
        ?>
        <form method="POST" action="customers_action.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string) $row['id']) ?>">
            <div class="panel span-8">
                <div class="panel-title"><i class="fa-solid fa-pen-to-square"></i> Edit Customer</div>
                <div style="margin-top:12px;">
                    <label>Customer Code</label><br>
                    <input type="text" name="customer_code" value="<?= htmlspecialchars((string) ($row['customer_code'] ?? '')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Name</label><br>
                    <input type="text" name="name" value="<?= htmlspecialchars((string) $row['name']) ?>" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Company Name</label><br>
                    <input type="text" name="company_name" value="<?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Contact Person</label><br>
                    <input type="text" name="contact_person" value="<?= htmlspecialchars((string) ($row['contact_person'] ?? '')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Phone</label><br>
                    <input type="text" name="phone" value="<?= htmlspecialchars((string) ($row['phone'] ?? '')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Email</label><br>
                    <input type="email" name="email" value="<?= htmlspecialchars((string) ($row['email'] ?? '')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Address</label><br>
                    <textarea name="address" style="width:100%;padding:8px;margin:6px 0;"><?= htmlspecialchars((string) ($row['address'] ?? '')) ?></textarea>
                    <label>City</label><br>
                    <input type="text" name="city" value="<?= htmlspecialchars((string) ($row['city'] ?? '')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Country</label><br>
                    <input type="text" name="country" value="<?= htmlspecialchars((string) ($row['country'] ?? '')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>TIN</label><br>
                    <input type="text" name="tin_number" value="<?= htmlspecialchars((string) ($row['tin_number'] ?? '')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Customer Group</label><br>
                    <select name="customer_group" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="retail" <?= (($row['customer_group'] ?? 'retail') === 'retail') ? 'selected' : '' ?>>Retail</option>
                        <option value="wholesale" <?= (($row['customer_group'] ?? 'retail') === 'wholesale') ? 'selected' : '' ?>>Wholesale</option>
                        <option value="corporate" <?= (($row['customer_group'] ?? 'retail') === 'corporate') ? 'selected' : '' ?>>Corporate</option>
                        <option value="individual" <?= (($row['customer_group'] ?? 'retail') === 'individual') ? 'selected' : '' ?>>Individual</option>
                    </select>
                    <label>Credit Limit</label><br>
                    <input type="number" step="0.01" name="credit_limit" value="<?= htmlspecialchars((string) ($row['credit_limit'] ?? '0.00')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Current Balance</label><br>
                    <input type="number" step="0.01" name="credit_balance" value="<?= htmlspecialchars((string) ($row['credit_balance'] ?? '0.00')) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Status</label><br>
                    <select name="is_active" style="width:100%;padding:8px;margin:6px 0;">
                        <option value="1" <?= ((int) ($row['is_active'] ?? 1)) === 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ((int) ($row['is_active'] ?? 1)) === 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <button class="action-btn" type="submit" style="margin-top:8px;">Update Customer</button>
                </div>
            </div>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'delete') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for delete.', false);
        $context = onyx_page_start('Delete Customer', 'Confirm deletion');
        ?>
        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-trash"></i> Confirm Delete</div>
            <p>Are you sure you want to delete this customer?</p>
            <form method="POST" action="customers_action.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $id) ?>">
                <button class="action-btn" type="submit">Confirm Delete</button>
            </form>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'view') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for view.', false);
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Customer not found.', false);
        $equipment = onyx_rows('SELECT * FROM customer_equipment WHERE tenant_id = :tenant_id AND customer_id = :customer_id ORDER BY installation_date DESC, equipment_type ASC', ['tenant_id' => $tenant_id, 'customer_id' => $id]);
        $maintenance = onyx_rows('SELECT * FROM customer_maintenance WHERE tenant_id = :tenant_id AND customer_id = :customer_id ORDER BY scheduled_on DESC, created_at DESC', ['tenant_id' => $tenant_id, 'customer_id' => $id]);
        $context = onyx_page_start('Customer Account', 'Account profile, financial info, and installed equipment for ' . $row['name']);
        ?>
        <div class="panel span-12">
            <div class="panel-title"><i class="fa-solid fa-id-badge"></i> <?= htmlspecialchars((string) $row['name']) ?></div>
            <div class="profile-grid" style="margin-top:12px;">
                <div class="profile-card"><strong>Profile</strong><span><?= htmlspecialchars((string) ($row['company_name'] ?? '-')) ?><br><?= htmlspecialchars((string) ($row['contact_person'] ?? '-')) ?><br><?= htmlspecialchars((string) ($row['phone'] ?? '-')) ?></span></div>
                <div class="profile-card"><strong>Financial</strong><span>Credit Limit: <?= htmlspecialchars((string) ($row['credit_limit'] ?? '0.00')) ?><br>Balance: <?= htmlspecialchars((string) ($row['credit_balance'] ?? '0.00')) ?><br>Status: <?= ((int) ($row['is_active'] ?? 1)) === 1 ? 'Active' : 'Inactive' ?></span></div>
                <div class="profile-card"><strong>Account</strong><span>Customer Code: <?= htmlspecialchars((string) ($row['customer_code'] ?? '-')) ?><br>TIN: <?= htmlspecialchars((string) ($row['tin_number'] ?? '-')) ?><br>Group: <?= htmlspecialchars((string) ($row['customer_group'] ?? 'retail')) ?></span></div>
            </div>
        </div>

        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-tools"></i> Add Installed Equipment</div>
            <form method="POST" action="customers_action.php" style="margin-top:12px;">
                <input type="hidden" name="action" value="add_equipment">
                <input type="hidden" name="customer_id" value="<?= htmlspecialchars((string) $id) ?>">
                <label>Equipment Type</label><br>
                <input type="text" name="equipment_type" required style="width:100%;padding:8px;margin:6px 0;">
                <label>Model</label><br>
                <input type="text" name="model" style="width:100%;padding:8px;margin:6px 0;">
                <label>Serial Number</label><br>
                <input type="text" name="serial_number" style="width:100%;padding:8px;margin:6px 0;">
                <label>Installation Date</label><br>
                <input type="date" name="installation_date" style="width:100%;padding:8px;margin:6px 0;">
                <label>Warranty Expiry</label><br>
                <input type="date" name="warranty_expiry" style="width:100%;padding:8px;margin:6px 0;">
                <label>Assigned Technician</label><br>
                <input type="text" name="assigned_technician" style="width:100%;padding:8px;margin:6px 0;">
                <label>Notes</label><br>
                <textarea name="notes" style="width:100%;padding:8px;margin:6px 0;"></textarea>
                <button class="action-btn" type="submit">Save Equipment</button>
            </form>
        </div>

        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-calendar-check"></i> Schedule Maintenance</div>
            <form method="POST" action="customers_action.php" style="margin-top:12px;">
                <input type="hidden" name="action" value="add_maintenance">
                <input type="hidden" name="customer_id" value="<?= htmlspecialchars((string) $id) ?>">
                <label>Title</label><br>
                <input type="text" name="title" required style="width:100%;padding:8px;margin:6px 0;">
                <label>Scheduled On</label><br>
                <input type="date" name="scheduled_on" style="width:100%;padding:8px;margin:6px 0;">
                <label>Status</label><br>
                <select name="status" style="width:100%;padding:8px;margin:6px 0;">
                    <option value="scheduled">Scheduled</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
                <label>Notes</label><br>
                <textarea name="notes" style="width:100%;padding:8px;margin:6px 0;"></textarea>
                <button class="action-btn" type="submit">Save Maintenance</button>
            </form>
        </div>

        <div class="panel span-12">
            <div class="panel-title"><i class="fa-solid fa-satellite-dish"></i> Installed Equipment</div>
            <?php if ($equipment === []): ?>
                <div style="padding-top:12px;">No equipment recorded yet.</div>
            <?php else: ?>
                <?php onyx_table(['Equipment', 'Model', 'Serial', 'Installed On', 'Warranty', 'Technician'], array_map(static fn (array $item): array => [
                    $item['equipment_type'],
                    $item['model'] ?: '-',
                    $item['serial_number'] ?: '-',
                    $item['installation_date'] ?: '-',
                    $item['warranty_expiry'] ?: '-',
                    $item['assigned_technician'] ?: '-',
                ], $equipment)); ?>
            <?php endif; ?>
        </div>

        <div class="panel span-12">
            <div class="panel-title"><i class="fa-solid fa-calendar-alt"></i> Maintenance Schedule</div>
            <?php if ($maintenance === []): ?>
                <div style="padding-top:12px;">No maintenance scheduled yet.</div>
            <?php else: ?>
                <?php onyx_table(['Title', 'Scheduled On', 'Status', 'Notes'], array_map(static fn (array $item): array => [
                    $item['title'],
                    $item['scheduled_on'] ?: '-',
                    $item['status'] ?: '-',
                    $item['notes'] ?: '-',
                ], $maintenance)); ?>
            <?php endif; ?>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'print') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for print.', false);
        $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Customer not found.', false);
        ?>
        <!DOCTYPE html>
        <html><head><title>Statement - <?= htmlspecialchars((string) $row['name']) ?></title></head><body>
        <h2>Customer Statement</h2>
        <p>Name: <?= htmlspecialchars((string) $row['name']) ?></p>
        <p>Company: <?= htmlspecialchars((string) ($row['company_name'] ?? '-')) ?></p>
        <p>Contact: <?= htmlspecialchars((string) ($row['contact_person'] ?? '-')) ?></p>
        <p>Email: <?= htmlspecialchars((string) ($row['email'] ?? '')) ?></p>
        <p>Phone: <?= htmlspecialchars((string) ($row['phone'] ?? '')) ?></p>
        <p>Address: <?= nl2br(htmlspecialchars((string) ($row['address'] ?? ''))) ?></p>
        <p>Credit Limit: <?= htmlspecialchars((string) ($row['credit_limit'] ?? '0.00')) ?></p>
        <p>Outstanding Balance: <?= htmlspecialchars((string) ($row['credit_balance'] ?? '0.00')) ?></p>
        <script>window.print();</script>
        </body></html>
        <?php
        exit();
    }

    if ($action === 'maintenance') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id for maintenance.', false);
        $stmt = $pdo->prepare('SELECT id, name FROM customers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Customer not found.', false);
        $context = onyx_page_start('Schedule Maintenance', 'Plan customer maintenance.');
        ?>
        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-calendar-check"></i> Schedule Maintenance</div>
            <form method="POST" action="customers_action.php" style="margin-top:12px;">
                <input type="hidden" name="action" value="add_maintenance">
                <input type="hidden" name="customer_id" value="<?= htmlspecialchars((string) $id) ?>">
                <label>Title</label><br>
                <input type="text" name="title" required style="width:100%;padding:8px;margin:6px 0;">
                <label>Scheduled On</label><br>
                <input type="date" name="scheduled_on" style="width:100%;padding:8px;margin:6px 0;">
                <label>Status</label><br>
                <select name="status" style="width:100%;padding:8px;margin:6px 0;">
                    <option value="scheduled">Scheduled</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
                <label>Notes</label><br>
                <textarea name="notes" style="width:100%;padding:8px;margin:6px 0;"></textarea>
                <button class="action-btn" type="submit">Save Maintenance</button>
            </form>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    redirect_back('Unsupported view action: ' . $action, false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $tin_number = trim($_POST['tin_number'] ?? '');
        $customer_group = trim($_POST['customer_group'] ?? 'retail');
        if ($name === '') redirect_back('Name is required.', false);
        $code = trim($_POST['customer_code'] ?? '');
        if ($code === '') $code = 'CUST-' . strtoupper(substr(uniqid(), -6));
        $credit_limit = (float)($_POST['credit_limit'] ?? 0);
        $credit_balance = (float)($_POST['credit_balance'] ?? 0);
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $stmt = $pdo->prepare('INSERT INTO customers (tenant_id, customer_code, name, company_name, contact_person, email, phone, address, city, country, tin_number, customer_group, credit_limit, credit_balance, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$tenant_id, $code, $name, $company_name, $contact_person, $email, $phone, $address, $city, $country, $tin_number, $customer_group, $credit_limit, $credit_balance, $is_active]);
        redirect_back('Customer added successfully.');
    }

    if ($action === 'edit') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id.', false);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $tin_number = trim($_POST['tin_number'] ?? '');
        $customer_group = trim($_POST['customer_group'] ?? 'retail');
        if ($name === '') redirect_back('Name is required.', false);
        $code = trim($_POST['customer_code'] ?? '');
        $credit_limit = (float)($_POST['credit_limit'] ?? 0);
        $credit_balance = (float)($_POST['credit_balance'] ?? 0);
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $stmt = $pdo->prepare('UPDATE customers SET customer_code = ?, name = ?, company_name = ?, contact_person = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, tin_number = ?, customer_group = ?, credit_limit = ?, credit_balance = ?, is_active = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$code, $name, $company_name, $contact_person, $email, $phone, $address, $city, $country, $tin_number, $customer_group, $credit_limit, $credit_balance, $is_active, $id, $tenant_id]);
        redirect_back('Customer updated successfully.');
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) redirect_back('Missing customer id.', false);
        $stmt = $pdo->prepare('DELETE FROM customer_equipment WHERE tenant_id = ? AND customer_id = ?');
        $stmt->execute([$tenant_id, $id]);
        $stmt = $pdo->prepare('DELETE FROM customer_maintenance WHERE tenant_id = ? AND customer_id = ?');
        $stmt->execute([$tenant_id, $id]);
        $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$id, $tenant_id]);
        redirect_back('Customer deleted successfully.');
    }

    if ($action === 'add_equipment') {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if ($customer_id <= 0) redirect_back('Customer is required.', false);
        $equipment_type = trim($_POST['equipment_type'] ?? '');
        if ($equipment_type === '') redirect_back('Equipment type is required.', false);
        $stmt = $pdo->prepare('INSERT INTO customer_equipment (tenant_id, customer_id, equipment_type, model, serial_number, installation_date, warranty_expiry, assigned_technician, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $customer_id, $equipment_type, trim($_POST['model'] ?? ''), trim($_POST['serial_number'] ?? ''), trim($_POST['installation_date'] ?? ''), trim($_POST['warranty_expiry'] ?? ''), trim($_POST['assigned_technician'] ?? ''), trim($_POST['notes'] ?? '')]);
        redirect_back('Equipment added successfully.');
    }

    if ($action === 'add_maintenance') {
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if ($customer_id <= 0) redirect_back('Customer is required.', false);
        $title = trim($_POST['title'] ?? '');
        if ($title === '') redirect_back('Maintenance title is required.', false);
        $stmt = $pdo->prepare('INSERT INTO customer_maintenance (tenant_id, customer_id, title, scheduled_on, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$tenant_id, $customer_id, $title, trim($_POST['scheduled_on'] ?? ''), trim($_POST['status'] ?? 'scheduled'), trim($_POST['notes'] ?? '')]);
        redirect_back('Maintenance scheduled successfully.');
    }

    redirect_back('Unsupported action.', false);
}
