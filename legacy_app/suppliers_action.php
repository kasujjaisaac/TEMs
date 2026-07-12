<?php
require_once __DIR__ . '/includes/erp_layout.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$tenant_id = onyx_tenant_id();
$pdo = onyx_db();

function redirect_back($msg = '', $success = true) {
    $q = '';
    if ($msg !== '') $q = ($success ? '?success=' : '?error=') . urlencode($msg);
    header('Location: suppliers.php' . $q);
    exit();
}

if (!$action) {
    redirect_back('No action specified.', false);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'add') {
        $context = onyx_page_start('Add Supplier', 'Create a new supplier record.');
        ?>
        <form method="POST" action="suppliers_action.php">
            <input type="hidden" name="action" value="add">
            <div class="panel span-6">
                <div class="panel-title"><i class="fa-solid fa-plus"></i> Add Supplier</div>
                <div style="margin-top:12px;">
                    <label>Company Name</label><br>
                    <input type="text" name="company_name" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Contact Person</label><br>
                    <input type="text" name="contact_person" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Phone</label><br>
                    <input type="text" name="phone" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Email</label><br>
                    <input type="email" name="email" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Address</label><br>
                    <textarea name="address" style="width:100%;padding:8px;margin:6px 0;"></textarea>
                    <label>TIN / Tax Number</label><br>
                    <input type="text" name="tin_number" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Credit Limit</label><br>
                    <input type="number" step="0.01" name="credit_limit" value="0.00" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Outstanding Balance</label><br>
                    <input type="number" step="0.01" name="credit_balance" value="0.00" style="width:100%;padding:8px;margin:6px 0;">
                    <button class="action-btn" type="submit" style="margin-top:8px;">Save Supplier</button>
                </div>
            </div>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'edit') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing supplier id for edit.', false);
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Supplier not found.', false);
        $context = onyx_page_start('Edit Supplier', 'Update supplier details.');
        ?>
        <form method="POST" action="suppliers_action.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
            <div class="panel span-6">
                <div class="panel-title"><i class="fa-solid fa-pen-to-square"></i> Edit Supplier</div>
                <div style="margin-top:12px;">
                    <label>Company Name</label><br>
                    <input type="text" name="company_name" value="<?= htmlspecialchars($row['company_name']) ?>" required style="width:100%;padding:8px;margin:6px 0;">
                    <label>Contact Person</label><br>
                    <input type="text" name="contact_person" value="<?= htmlspecialchars($row['contact_person']) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Phone</label><br>
                    <input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Email</label><br>
                    <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Address</label><br>
                    <textarea name="address" style="width:100%;padding:8px;margin:6px 0;"><?= htmlspecialchars($row['address']) ?></textarea>
                    <label>TIN / Tax Number</label><br>
                    <input type="text" name="tin_number" value="<?= htmlspecialchars($row['tin_number']) ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Credit Limit</label><br>
                    <input type="number" step="0.01" name="credit_limit" value="<?= htmlspecialchars($row['credit_limit'] ?? '0.00') ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <label>Outstanding Balance</label><br>
                    <input type="number" step="0.01" name="credit_balance" value="<?= htmlspecialchars($row['credit_balance'] ?? '0.00') ?>" style="width:100%;padding:8px;margin:6px 0;">
                    <button class="action-btn" type="submit" style="margin-top:8px;">Update Supplier</button>
                </div>
            </div>
        </form>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'delete') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing supplier id for delete.', false);
        $context = onyx_page_start('Delete Supplier', 'Confirm deletion');
        ?>
        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-trash"></i> Confirm Delete</div>
            <p>Are you sure you want to delete this supplier?</p>
            <form method="POST" action="suppliers_action.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <button class="action-btn" type="submit">Confirm Delete</button>
            </form>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'view') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) redirect_back('Missing supplier id for view.', false);
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Supplier not found.', false);
        $context = onyx_page_start('Supplier Details', $row['company_name']);
        ?>
        <div class="panel span-6">
            <div class="panel-title"><i class="fa-solid fa-industry"></i> <?= htmlspecialchars($row['company_name']) ?></div>
            <div style="padding-top:12px;">
                <p><strong>Contact:</strong> <?= htmlspecialchars($row['contact_person']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($row['phone']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($row['address'])) ?></p>
                <p><strong>TIN:</strong> <?= htmlspecialchars($row['tin_number']) ?></p>
                <p><strong>Credit Limit:</strong> <?= htmlspecialchars($row['credit_limit'] ?? '0.00') ?></p>
                <p><strong>Outstanding Balance:</strong> <?= htmlspecialchars($row['credit_balance'] ?? '0.00') ?></p>
            </div>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    if ($action === 'statement') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $suppliers = onyx_rows('SELECT id, company_name FROM suppliers WHERE tenant_id = :tenant_id ORDER BY company_name ASC', ['tenant_id' => $tenant_id]);
            $context = onyx_page_start('Supplier Statement', 'Select a supplier to view statement');
            ?>
            <form method="GET" action="suppliers_action.php">
                <input type="hidden" name="action" value="statement">
                <label>Supplier</label><br>
                <select name="id" style="padding:8px;width:320px;margin:8px 0;">
                    <option value="">-- choose supplier --</option>
                    <?php foreach ($suppliers as $s): ?>
                        <option value="<?= htmlspecialchars($s['id']) ?>"><?= htmlspecialchars($s['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div style="margin-top:8px;"><button class="action-btn" type="submit">View Statement</button></div>
            </form>
            <?php
            onyx_page_end();
            exit();
        }
        $stmt = $pdo->prepare('SELECT * FROM suppliers WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenant_id]);
        $row = $stmt->fetch();
        if (!$row) redirect_back('Supplier not found.', false);
        $context = onyx_page_start('Supplier Statement', $row['company_name']);
        ?>
        <div class="panel span-12">
            <div class="panel-title">Statement for <?= htmlspecialchars($row['company_name']) ?></div>
            <div style="padding-top:12px;">
                <p><strong>Credit Limit:</strong> <?= htmlspecialchars($row['credit_limit'] ?? '0.00') ?></p>
                <p><strong>Outstanding Balance:</strong> <?= htmlspecialchars($row['credit_balance'] ?? '0.00') ?></p>
                <p>No statement data available.</p>
            </div>
        </div>
        <?php
        onyx_page_end();
        exit();
    }

    redirect_back('Unsupported view action: ' . $action, false);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $company_name = trim($_POST['company_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $tin_number = trim($_POST['tin_number'] ?? '');
        if ($company_name === '') redirect_back('Company name is required.', false);
        $code = 'SUP-' . strtoupper(substr(uniqid(), -6));
        $credit_limit = (float)($_POST['credit_limit'] ?? 0);
        $credit_balance = (float)($_POST['credit_balance'] ?? 0);
        $stmt = $pdo->prepare('INSERT INTO suppliers (tenant_id, supplier_code, company_name, contact_person, phone, email, address, tin_number, credit_limit, credit_balance, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$tenant_id, $code, $company_name, $contact_person, $phone, $email, $address, $tin_number, $credit_limit, $credit_balance]);
        redirect_back('Supplier added successfully.');
    }

    if ($action === 'edit') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) redirect_back('Missing supplier id.', false);
        $company_name = trim($_POST['company_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $tin_number = trim($_POST['tin_number'] ?? '');
        if ($company_name === '') redirect_back('Company name is required.', false);
        $credit_limit = (float)($_POST['credit_limit'] ?? 0);
        $credit_balance = (float)($_POST['credit_balance'] ?? 0);
        $stmt = $pdo->prepare('UPDATE suppliers SET company_name = ?, contact_person = ?, phone = ?, email = ?, address = ?, tin_number = ?, credit_limit = ?, credit_balance = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$company_name, $contact_person, $phone, $email, $address, $tin_number, $credit_limit, $credit_balance, $id, $tenant_id]);
        redirect_back('Supplier updated successfully.');
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) redirect_back('Missing supplier id.', false);
        $stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$id, $tenant_id]);
        redirect_back('Supplier deleted successfully.');
    }

    redirect_back('Unsupported action.', false);
}
