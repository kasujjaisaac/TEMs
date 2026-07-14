<?php
$context = onyx_page_start('Contracts & Roles', 'Assign employee contracts, start dates, expiry dates, supervisors, departments, and role terms.');
$tenant_id = onyx_tenant_id();
$pdo = onyx_db();

onyx_hr_ensure_schema($pdo);
onyx_hr_seed_employees($pdo, $tenant_id);

function hr_contract_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_contract') {
    require_permission('hr.manage');

    $id = (int) ($_POST['id'] ?? 0);
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    $employee = $employeeId > 0 ? onyx_row('SELECT id, department, job_title FROM hr_employees WHERE id = :id AND tenant_id = :tenant_id', ['id' => $employeeId, 'tenant_id' => $tenant_id]) : false;

    if (! $employee) {
        header('Location: ' . onyx_legacy_url('hr_contracts.php?error=' . urlencode('Select a valid employee before saving a contract.')));
        exit();
    }

    $data = [
        $employeeId,
        trim($_POST['contract_type'] ?? 'Full time'),
        trim($_POST['department'] ?? ($employee['department'] ?? '')),
        trim($_POST['job_title'] ?? ($employee['job_title'] ?? '')),
        trim($_POST['supervisor'] ?? ''),
        ($_POST['start_date'] ?? '') !== '' ? $_POST['start_date'] : null,
        ($_POST['end_date'] ?? '') !== '' ? $_POST['end_date'] : null,
        ($_POST['probation_end'] ?? '') !== '' ? $_POST['probation_end'] : null,
        trim($_POST['status'] ?? 'Active'),
        trim($_POST['role_summary'] ?? ''),
    ];

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE hr_employee_contracts SET employee_id = ?, contract_type = ?, department = ?, job_title = ?, supervisor = ?, start_date = ?, end_date = ?, probation_end = ?, status = ?, role_summary = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute(array_merge($data, [$id, $tenant_id]));
        header('Location: ' . onyx_legacy_url('hr_contracts.php?success=' . urlencode('Contract updated successfully.')));
        exit();
    }

    $stmt = $pdo->prepare('INSERT INTO hr_employee_contracts (tenant_id, employee_id, contract_type, department, job_title, supervisor, start_date, end_date, probation_end, status, role_summary, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute(array_merge([$tenant_id], $data));

    header('Location: ' . onyx_legacy_url('hr_contracts.php?success=' . urlencode('Contract assigned successfully.')));
    exit();
}

$employees = onyx_rows('SELECT id, employee_code, full_name, department, job_title FROM hr_employees WHERE tenant_id = :tenant_id ORDER BY employee_code ASC', ['tenant_id' => $tenant_id]);
$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0 ? onyx_row('SELECT * FROM hr_employee_contracts WHERE id = :id AND tenant_id = :tenant_id', ['id' => $editId, 'tenant_id' => $tenant_id]) : false;
$showForm = ($_GET['action'] ?? '') === 'create' || $editing;
$selectedEmployeeId = (int) ($editing['employee_id'] ?? ($_GET['employee_id'] ?? 0));

$rows = onyx_rows(
    'SELECT e.id AS employee_id, e.employee_code, e.full_name, e.department AS employee_department, e.job_title AS employee_job_title,
            c.id AS contract_id, c.contract_type, c.department, c.job_title, c.supervisor, c.start_date, c.end_date, c.probation_end, c.status, c.role_summary
     FROM hr_employees e
     LEFT JOIN (
        SELECT c1.*
        FROM hr_employee_contracts c1
        INNER JOIN (
            SELECT tenant_id, employee_id, MAX(id) AS latest_id
            FROM hr_employee_contracts
            WHERE tenant_id = :latest_tenant_id
            GROUP BY tenant_id, employee_id
        ) latest ON latest.latest_id = c1.id AND latest.tenant_id = c1.tenant_id
     ) c ON c.employee_id = e.id AND c.tenant_id = e.tenant_id
     WHERE e.tenant_id = :tenant_id
     ORDER BY e.employee_code ASC',
    ['latest_tenant_id' => $tenant_id, 'tenant_id' => $tenant_id]
);

$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+30 days'));
$assignedCount = count(array_filter($rows, static fn (array $row): bool => ! empty($row['contract_id'])));
$activeCount = count(array_filter($rows, static fn (array $row): bool => ($row['status'] ?? '') === 'Active'));
$expiringCount = count(array_filter($rows, static fn (array $row): bool => ! empty($row['end_date']) && $row['end_date'] >= $today && $row['end_date'] <= $soon));
$missingCount = count($rows) - $assignedCount;
?>

<style>
    .contract-register,.contract-register *{border-radius:0!important}.contract-register{display:grid;gap:14px}.contract-hero{align-items:center;background:var(--onyx-surface);border:1px solid var(--onyx-border);display:flex;gap:12px;justify-content:space-between;padding:10px 14px}.contract-title{align-items:center;display:flex;gap:10px;min-width:0}.contract-icon{align-items:center;background:#fff;color:#050506;display:flex;flex:0 0 30px;font-size:11px;height:30px;justify-content:center;width:30px}.contract-hero h2{color:#fff;font-size:16px;font-weight:900;line-height:1.1;margin:0}.contract-hero p{color:var(--onyx-muted);font-size:10px;font-weight:600;line-height:1.25;margin:2px 0 0}.contract-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font-size:10px;font-weight:900;gap:7px;min-height:30px;padding:0 10px;text-decoration:none;text-transform:uppercase;white-space:nowrap}.contract-btn.primary{background:#fff;color:#050506}.contract-stats{display:grid;gap:8px;grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:max-content}.contract-stat{align-self:start;background:var(--onyx-surface);border:1px solid var(--onyx-border);min-height:42px;padding:7px 9px}.contract-stat span{color:var(--onyx-muted);display:block;font-size:8px;font-weight:900;line-height:1.1;text-transform:uppercase}.contract-stat strong{color:#fff;display:block;font-size:13px;font-weight:900;line-height:1;margin-top:3px}.contract-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:14px;overflow:hidden}.contract-panel-head{align-items:center;border-bottom:1px solid rgba(255,255,255,.07);display:flex;gap:10px;justify-content:space-between;margin-bottom:12px;padding-bottom:10px}.contract-panel-head h3{color:#fff;font-size:12px;font-weight:900;margin:0;text-transform:uppercase}.contract-muted{color:var(--onyx-muted);display:block;font-size:9px;font-weight:700;line-height:1.3;margin-top:3px}.contract-grid{display:grid;gap:10px;grid-template-columns:repeat(12,minmax(0,1fr))}.contract-field{display:grid;gap:5px;grid-column:span 3;min-width:0}.contract-field.wide{grid-column:span 6}.contract-field.full{grid-column:span 12}.contract-field label{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.contract-field input,.contract-field select,.contract-field textarea{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:36px;padding:8px 10px;width:100%}.contract-field textarea{min-height:74px;resize:vertical}.contract-field select option{background:#050506;color:#fff}.contract-form-actions,.contract-actions{align-items:center;display:flex;flex-wrap:nowrap;gap:6px;white-space:nowrap}.contract-form-actions{justify-content:flex-end;margin-top:12px}.contract-table-wrap{max-width:calc(100vw - 340px);overflow-x:auto}.contract-table{border-collapse:collapse;table-layout:fixed;width:1220px}.contract-table th,.contract-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:8px;text-align:left;vertical-align:middle}.contract-table th{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.contract-name strong{color:#fff;display:block;font-size:11px}.contract-status{border:1px solid rgba(255,255,255,.12);display:inline-flex;font-size:9px;font-weight:900;padding:4px 7px;text-transform:uppercase;white-space:nowrap}.contract-status.ok{color:#8ff0c3}.contract-status.warn{color:#ffd27a}.contract-status.missing{color:#ff8a8a}.contract-alert{border:1px solid rgba(143,240,195,.26);color:#8ff0c3;font-size:11px;font-weight:800;padding:10px 12px}.contract-alert.error{border-color:rgba(255,138,138,.34);color:#ff8a8a}@media(max-width:1180px){.contract-stats{grid-template-columns:repeat(2,1fr)}.contract-table-wrap{max-width:calc(100vw - 36px)}}@media(max-width:820px){.contract-field,.contract-field.wide{grid-column:span 6}}@media(max-width:640px){.contract-hero{align-items:flex-start;flex-direction:column}.contract-stats{grid-template-columns:1fr}.contract-field,.contract-field.wide{grid-column:span 12}.contract-btn{justify-content:center;width:100%}}
</style>

<div class="contract-register">
    <?php if (! empty($_GET['success'])): ?><div class="contract-alert"><?= hr_contract_h($_GET['success']) ?></div><?php endif; ?>
    <?php if (! empty($_GET['error'])): ?><div class="contract-alert error"><?= hr_contract_h($_GET['error']) ?></div><?php endif; ?>

    <section class="contract-hero">
        <div class="contract-title">
            <div class="contract-icon"><i class="fa-solid fa-file-signature"></i></div>
            <div>
                <h2>Contracts & Roles</h2>
                <p>Assign contracts to existing employees and track start, expiry, supervisor, and role terms.</p>
            </div>
        </div>
        <a class="contract-btn primary" href="<?= hr_contract_h(onyx_legacy_url('hr_contracts.php?action=create')) ?>"><i class="fa-solid fa-plus"></i> Add Contract</a>
    </section>

    <section class="contract-stats">
        <div class="contract-stat"><span>Employees</span><strong><?= hr_contract_h(count($rows)) ?></strong></div>
        <div class="contract-stat"><span>Assigned Contracts</span><strong><?= hr_contract_h($assignedCount) ?></strong></div>
        <div class="contract-stat"><span>Expiring Soon</span><strong><?= hr_contract_h($expiringCount) ?></strong></div>
        <div class="contract-stat"><span>No Contract</span><strong><?= hr_contract_h($missingCount) ?></strong></div>
    </section>

    <?php if ($showForm): ?>
        <section class="contract-panel">
            <div class="contract-panel-head">
                <div>
                    <h3><?= $editing ? 'Edit Contract' : 'Assign Contract' ?></h3>
                    <span class="contract-muted">Pick an employee from the employee table and define contract dates and role terms.</span>
                </div>
                <a class="contract-btn" href="<?= hr_contract_h(onyx_legacy_url('hr_contracts.php')) ?>"><i class="fa-solid fa-xmark"></i> Close</a>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="save_contract">
                <input type="hidden" name="id" value="<?= hr_contract_h($editing['id'] ?? '') ?>">
                <div class="contract-grid">
                    <div class="contract-field wide"><label>Employee</label><select name="employee_id" required><option value="">Select employee</option><?php foreach ($employees as $employee): ?><option value="<?= hr_contract_h($employee['id']) ?>" <?= $selectedEmployeeId === (int) $employee['id'] ? 'selected' : '' ?>><?= hr_contract_h($employee['employee_code'] . ' - ' . $employee['full_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="contract-field"><label>Contract Type</label><select name="contract_type"><?php foreach (['Permanent', 'Full time', 'Part time', 'Contract', 'Casual', 'Probation'] as $option): ?><option <?= ($editing['contract_type'] ?? 'Full time') === $option ? 'selected' : '' ?>><?= hr_contract_h($option) ?></option><?php endforeach; ?></select></div>
                    <div class="contract-field"><label>Status</label><select name="status"><?php foreach (['Active', 'Probation', 'Review due', 'Expired', 'Terminated'] as $option): ?><option <?= ($editing['status'] ?? 'Active') === $option ? 'selected' : '' ?>><?= hr_contract_h($option) ?></option><?php endforeach; ?></select></div>
                    <div class="contract-field"><label>Department</label><input name="department" value="<?= hr_contract_h($editing['department'] ?? '') ?>" placeholder="Department"></div>
                    <div class="contract-field"><label>Job Title</label><input name="job_title" value="<?= hr_contract_h($editing['job_title'] ?? '') ?>" placeholder="Role / position"></div>
                    <div class="contract-field"><label>Supervisor</label><input name="supervisor" value="<?= hr_contract_h($editing['supervisor'] ?? '') ?>" placeholder="Supervisor name"></div>
                    <div class="contract-field"><label>Start Date</label><input name="start_date" type="date" value="<?= hr_contract_h($editing['start_date'] ?? date('Y-m-d')) ?>"></div>
                    <div class="contract-field"><label>Expiry Date</label><input name="end_date" type="date" value="<?= hr_contract_h($editing['end_date'] ?? '') ?>"></div>
                    <div class="contract-field"><label>Probation End</label><input name="probation_end" type="date" value="<?= hr_contract_h($editing['probation_end'] ?? '') ?>"></div>
                    <div class="contract-field full"><label>Role Summary</label><textarea name="role_summary" placeholder="Duties, reporting line, access needs, contract notes"><?= hr_contract_h($editing['role_summary'] ?? '') ?></textarea></div>
                </div>
                <div class="contract-form-actions">
                    <a class="contract-btn" href="<?= hr_contract_h(onyx_legacy_url('hr_contracts.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
                    <button class="contract-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> <?= $editing ? 'Update Contract' : 'Save Contract' ?></button>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="contract-panel">
        <div class="contract-panel-head">
            <div>
                <h3>Employee Contract Register</h3>
                <span class="contract-muted">Every employee is listed here with the latest contract assignment.</span>
            </div>
            <a class="contract-btn" href="<?= hr_contract_h(onyx_legacy_url('human_resources.php')) ?>"><i class="fa-solid fa-users"></i> Employees</a>
        </div>
        <div class="contract-table-wrap">
            <table class="contract-table">
                <thead>
                    <tr>
                        <th style="width:90px">Code</th>
                        <th style="width:210px">Employee</th>
                        <th style="width:120px">Contract</th>
                        <th style="width:140px">Department</th>
                        <th style="width:150px">Role</th>
                        <th style="width:105px">Start Date</th>
                        <th style="width:105px">Expiry Date</th>
                        <th style="width:110px">Status</th>
                        <th style="width:175px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php $hasContract = ! empty($row['contract_id']); ?>
                        <tr>
                            <td><?= hr_contract_h($row['employee_code']) ?></td>
                            <td class="contract-name"><strong><?= hr_contract_h($row['full_name']) ?></strong><span class="contract-muted"><?= hr_contract_h($row['employee_job_title'] ?: 'No role') ?></span></td>
                            <td><?= hr_contract_h($row['contract_type'] ?: 'No contract') ?></td>
                            <td><?= hr_contract_h($row['department'] ?: $row['employee_department'] ?: '-') ?></td>
                            <td><?= hr_contract_h($row['job_title'] ?: $row['employee_job_title'] ?: '-') ?></td>
                            <td><?= hr_contract_h($row['start_date'] ?: '-') ?></td>
                            <td><?= hr_contract_h($row['end_date'] ?: '-') ?></td>
                            <td><span class="contract-status <?= $hasContract ? (($row['status'] ?? '') === 'Active' ? 'ok' : 'warn') : 'missing' ?>"><?= hr_contract_h($hasContract ? $row['status'] : 'No contract') ?></span></td>
                            <td>
                                <div class="contract-actions">
                                    <?php if ($hasContract): ?>
                                        <a class="contract-btn primary" href="<?= hr_contract_h(onyx_legacy_url('hr_contracts.php?edit=' . (int) $row['contract_id'])) ?>"><i class="fa-solid fa-pen"></i> Edit Contract</a>
                                    <?php else: ?>
                                        <a class="contract-btn primary" href="<?= hr_contract_h(onyx_legacy_url('hr_contracts.php?action=create&employee_id=' . (int) $row['employee_id'])) ?>"><i class="fa-solid fa-plus"></i> Add Contract</a>
                                    <?php endif; ?>
                                    <a class="contract-btn" href="<?= hr_contract_h(onyx_legacy_url('hr_employee.php?id=' . (int) $row['employee_id'])) ?>"><i class="fa-solid fa-eye"></i> Profile</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php onyx_page_end(); ?>
