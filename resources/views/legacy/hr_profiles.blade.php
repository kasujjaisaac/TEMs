<?php
$context = onyx_page_start('Add Employee', 'Create and update employee bio data, contacts, payroll details, emergency contacts, and profile notes.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();
$pdo = onyx_db();

onyx_hr_ensure_schema($pdo);
onyx_hr_seed_employees($pdo, $tenant_id);

function hr_profile_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function hr_profile_create_user_for_employee(PDO $pdo, int $tenantId, array $employee, string $temporaryPassword): array
{
    \App\Models\Role::ensureDefaultsForTenant($tenantId);

    $email = strtolower(trim((string) $employee['email']));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('A valid employee email is required to create a login account.');
    }

    $existing = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $existing->execute([$email]);
    if ($existing->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException('A user account already exists for this employee email.');
    }

    $role = onyx_row('SELECT id, slug FROM roles WHERE tenant_id = :tenant_id AND slug = :slug LIMIT 1', ['tenant_id' => $tenantId, 'slug' => 'viewer']);

    $stmt = $pdo->prepare('INSERT INTO users (tenant_id, role_id, name, email, phone, department, password, role, is_active, password_changed_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NOW(), NOW())');
    $stmt->execute([
        $tenantId,
        $role['id'] ?? null,
        $employee['full_name'],
        $email,
        $employee['phone'],
        $employee['department'],
        password_hash($temporaryPassword !== '' ? $temporaryPassword : '123', PASSWORD_BCRYPT),
        $role['slug'] ?? 'viewer',
        in_array($employee['status'], ['Active', 'Onboarding'], true) ? 1 : 0,
    ]);

    return ['email' => $email, 'temporary_password' => $temporaryPassword !== '' ? $temporaryPassword : '123'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_employee') {
    require_permission('hr.manage');

    $id = (int) ($_POST['id'] ?? 0);
    $employeeCode = trim($_POST['employee_code'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $temporaryPassword = trim($_POST['temporary_password'] ?? '123');

    if ($employeeCode === '' || $fullName === '') {
        header('Location: ' . onyx_legacy_url('hr_profiles.php?error=' . urlencode('Employee code and full name are required.')));
        exit();
    }

    $data = [
        $employeeCode,
        $fullName,
        trim($_POST['gender'] ?? ''),
        ($_POST['date_of_birth'] ?? '') !== '' ? $_POST['date_of_birth'] : null,
        trim($_POST['department'] ?? ''),
        trim($_POST['job_title'] ?? ''),
        trim($_POST['employment_type'] ?? 'Full time'),
        trim($_POST['phone'] ?? ''),
        trim($_POST['email'] ?? ''),
        trim($_POST['national_id'] ?? ''),
        trim($_POST['bank_wallet'] ?? ''),
        trim($_POST['address'] ?? ''),
        trim($_POST['next_of_kin'] ?? ''),
        trim($_POST['kin_phone'] ?? ''),
        (float) ($_POST['basic_pay'] ?? 0),
        trim($_POST['status'] ?? 'Active'),
        trim($_POST['notes'] ?? ''),
    ];

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE hr_employees SET employee_code = ?, full_name = ?, gender = ?, date_of_birth = ?, department = ?, job_title = ?, employment_type = ?, phone = ?, email = ?, national_id = ?, bank_wallet = ?, address = ?, next_of_kin = ?, kin_phone = ?, basic_pay = ?, status = ?, notes = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
            $stmt->execute(array_merge($data, [$id, $tenant_id]));
            header('Location: ' . onyx_legacy_url('human_resources.php?success=' . urlencode('Employee profile updated successfully.')));
            exit();
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO hr_employees (tenant_id, employee_code, full_name, gender, date_of_birth, department, job_title, employment_type, phone, email, national_id, bank_wallet, address, next_of_kin, kin_phone, basic_pay, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute(array_merge([$tenant_id], $data));

        $login = hr_profile_create_user_for_employee($pdo, $tenant_id, [
            'employee_code' => $employeeCode,
            'full_name' => $fullName,
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'department' => trim($_POST['department'] ?? ''),
            'status' => trim($_POST['status'] ?? 'Active'),
        ], $temporaryPassword);

        $pdo->commit();

        header('Location: ' . onyx_legacy_url('human_resources.php?success=' . urlencode('Employee added. Login email: ' . $login['email'] . ' / temporary password: ' . $login['temporary_password'])));
        exit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = str_contains(strtolower($e->getMessage()), 'duplicate') ? 'Employee code already exists.' : 'Unable to save employee profile.';
        header('Location: ' . onyx_legacy_url('hr_profiles.php?error=' . urlencode($message) . ($id > 0 ? '&edit=' . $id : '&action=create')));
        exit();
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0 ? onyx_row('SELECT * FROM hr_employees WHERE id = :id AND tenant_id = :tenant_id', ['id' => $editId, 'tenant_id' => $tenant_id]) : false;
$employeeCount = (int) onyx_scalar('SELECT COUNT(*) FROM hr_employees WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id], 0);
$nextCode = 'EMP-' . str_pad((string) ($employeeCount + 1), 3, '0', STR_PAD_LEFT);
$title = $editing ? 'Edit Employee' : 'Add Employee';
?>

<style>
    .hr-form-page,.hr-form-page *{border-radius:0!important}.hr-form-page{display:grid;gap:14px;max-width:1180px}.hr-form-hero{align-items:center;background:var(--onyx-surface);border:1px solid var(--onyx-border);display:flex;gap:12px;justify-content:space-between;padding:10px 14px}.hr-form-title{align-items:center;display:flex;gap:10px;min-width:0}.hr-form-icon{align-items:center;background:#fff;color:#050506;display:flex;flex:0 0 30px;font-size:11px;height:30px;justify-content:center;width:30px}.hr-form-hero h2{color:#fff;font-size:16px;font-weight:900;line-height:1.1;margin:0}.hr-form-hero p{color:var(--onyx-muted);font-size:10px;font-weight:600;line-height:1.25;margin:2px 0 0}.hr-form-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:14px}.hr-section-title{align-items:center;border-bottom:1px solid rgba(255,255,255,.07);color:#fff;display:flex;font-size:11px;font-weight:900;gap:8px;margin:0 0 12px;padding-bottom:10px;text-transform:uppercase}.hr-form-grid{display:grid;gap:10px;grid-template-columns:repeat(12,minmax(0,1fr))}.hr-field{display:grid;gap:5px;grid-column:span 3;min-width:0}.hr-field.wide{grid-column:span 6}.hr-field.full{grid-column:span 12}.hr-field label{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.hr-field input,.hr-field select,.hr-field textarea{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:36px;padding:8px 10px;width:100%}.hr-field textarea{min-height:74px;resize:vertical}.hr-field select option{background:#050506;color:#fff}.hr-actions{align-items:center;display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}.hr-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font-size:10px;font-weight:900;gap:7px;min-height:32px;padding:0 11px;text-decoration:none;text-transform:uppercase}.hr-btn.primary{background:#fff;color:#050506}.hr-alert{border:1px solid rgba(255,138,138,.34);color:#ff8a8a;font-size:11px;font-weight:800;padding:10px 12px}@media(max-width:900px){.hr-field,.hr-field.wide{grid-column:span 6}}@media(max-width:640px){.hr-form-hero{align-items:flex-start;flex-direction:column}.hr-field,.hr-field.wide{grid-column:span 12}.hr-actions{justify-content:stretch}.hr-btn{justify-content:center;width:100%}}
</style>

<div class="hr-form-page">
    <?php if (! empty($_GET['error'])): ?><div class="hr-alert"><?= hr_profile_h($_GET['error']) ?></div><?php endif; ?>

    <section class="hr-form-hero">
        <div class="hr-form-title">
            <div class="hr-form-icon"><i class="fa-solid fa-user-plus"></i></div>
            <div>
                <h2><?= hr_profile_h($title) ?></h2>
                <p><?= $editing ? 'Update employee details and keep the master register accurate.' : 'Capture a complete employee profile before adding documents and payroll details.' ?></p>
            </div>
        </div>
        <a class="hr-btn" href="<?= hr_profile_h(onyx_legacy_url('human_resources.php')) ?>"><i class="fa-solid fa-table-list"></i> Employees</a>
    </section>

    <form method="post" class="hr-form-page">
        <input type="hidden" name="action" value="save_employee">
        <input type="hidden" name="id" value="<?= hr_profile_h($editing['id'] ?? '') ?>">

        <section class="hr-form-panel">
            <h3 class="hr-section-title"><i class="fa-solid fa-id-card"></i> Employee Details</h3>
            <div class="hr-form-grid">
                <div class="hr-field"><label>Employee Code</label><input name="employee_code" required value="<?= hr_profile_h($editing['employee_code'] ?? $nextCode) ?>"></div>
                <div class="hr-field wide"><label>Full Name</label><input name="full_name" required value="<?= hr_profile_h($editing['full_name'] ?? '') ?>" placeholder="Employee full name"></div>
                <div class="hr-field"><label>Gender</label><select name="gender"><?php foreach (['Female', 'Male', 'Prefer not to say'] as $option): ?><option <?= ($editing['gender'] ?? '') === $option ? 'selected' : '' ?>><?= hr_profile_h($option) ?></option><?php endforeach; ?></select></div>
                <div class="hr-field"><label>Date of Birth</label><input name="date_of_birth" type="date" value="<?= hr_profile_h($editing['date_of_birth'] ?? '') ?>"></div>
                <div class="hr-field"><label>Department</label><input name="department" value="<?= hr_profile_h($editing['department'] ?? '') ?>" placeholder="Department"></div>
                <div class="hr-field"><label>Job Title</label><input name="job_title" value="<?= hr_profile_h($editing['job_title'] ?? '') ?>" placeholder="Role / position"></div>
                <div class="hr-field"><label>Employment Type</label><select name="employment_type"><?php foreach (['Full time', 'Part time', 'Contract', 'Casual'] as $option): ?><option <?= ($editing['employment_type'] ?? 'Full time') === $option ? 'selected' : '' ?>><?= hr_profile_h($option) ?></option><?php endforeach; ?></select></div>
                <div class="hr-field"><label>Status</label><select name="status"><?php foreach (['Active', 'Onboarding', 'Suspended', 'Exited'] as $option): ?><option <?= ($editing['status'] ?? 'Active') === $option ? 'selected' : '' ?>><?= hr_profile_h($option) ?></option><?php endforeach; ?></select></div>
            </div>
        </section>

        <?php if (! $editing): ?>
        <section class="hr-form-panel">
            <h3 class="hr-section-title"><i class="fa-solid fa-right-to-bracket"></i> System Login</h3>
            <div class="hr-form-grid">
                <div class="hr-field"><label>Temporary Password</label><input name="temporary_password" value="<?= hr_profile_h($_POST['temporary_password'] ?? '123') ?>"></div>
                <div class="hr-field wide"><label>Default Access</label><input value="Email login / OTP required / Viewer role / must change password on first login" disabled></div>
            </div>
        </section>
        <?php endif; ?>

        <section class="hr-form-panel">
            <h3 class="hr-section-title"><i class="fa-solid fa-address-book"></i> Contacts and Payroll</h3>
            <div class="hr-form-grid">
                <div class="hr-field"><label>Phone</label><input name="phone" value="<?= hr_profile_h($editing['phone'] ?? '') ?>" placeholder="Primary phone"></div>
                <div class="hr-field"><label>Email</label><input name="email" type="email" value="<?= hr_profile_h($editing['email'] ?? '') ?>" placeholder="Work or personal email" <?= $editing ? '' : 'required' ?>></div>
                <div class="hr-field"><label>National ID</label><input name="national_id" value="<?= hr_profile_h($editing['national_id'] ?? '') ?>" placeholder="NIN / ID number"></div>
                <div class="hr-field"><label>Bank / Wallet</label><input name="bank_wallet" value="<?= hr_profile_h($editing['bank_wallet'] ?? '') ?>" placeholder="Bank account or mobile money"></div>
                <div class="hr-field"><label>Basic Pay</label><input name="basic_pay" type="number" step="0.01" value="<?= hr_profile_h($editing['basic_pay'] ?? '0') ?>"></div>
                <div class="hr-field"><label>Next of Kin</label><input name="next_of_kin" value="<?= hr_profile_h($editing['next_of_kin'] ?? '') ?>" placeholder="Name"></div>
                <div class="hr-field"><label>Kin Phone</label><input name="kin_phone" value="<?= hr_profile_h($editing['kin_phone'] ?? '') ?>" placeholder="Phone"></div>
                <div class="hr-field wide"><label>Residential Address</label><input name="address" value="<?= hr_profile_h($editing['address'] ?? '') ?>" placeholder="Village, parish, district"></div>
                <div class="hr-field full"><label>Notes</label><textarea name="notes" rows="2" placeholder="Contract, payroll, medical, document notes"><?= hr_profile_h($editing['notes'] ?? '') ?></textarea></div>
            </div>
        </section>

        <div class="hr-actions">
            <?php if ($editing): ?><a class="hr-btn" href="<?= hr_profile_h(onyx_legacy_url('hr_employee.php?id=' . (int) $editing['id'])) ?>"><i class="fa-solid fa-eye"></i> View Profile</a><?php endif; ?>
            <a class="hr-btn" href="<?= hr_profile_h(onyx_legacy_url('human_resources.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <button class="hr-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $editing ? 'Update Employee' : 'Add Employee' ?></button>
        </div>
    </form>
</div>

<?php onyx_page_end(); ?>
