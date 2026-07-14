<?php
$context = onyx_page_start('Employees', 'Employee master register, profile actions, documents, departments, and payroll status.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();
$pdo = onyx_db();

onyx_hr_ensure_schema($pdo);
onyx_hr_seed_employees($pdo, $tenant_id);

function hr_register_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_employee') {
    require_permission('hr.manage');

    $employeeId = (int) ($_POST['id'] ?? 0);
    if ($employeeId > 0) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('DELETE FROM hr_employee_documents WHERE employee_id = ? AND tenant_id = ?');
            $stmt->execute([$employeeId, $tenant_id]);

            $stmt = $pdo->prepare('DELETE FROM hr_employees WHERE id = ? AND tenant_id = ?');
            $stmt->execute([$employeeId, $tenant_id]);

            $pdo->commit();
            header('Location: ' . onyx_legacy_url('human_resources.php?success=' . urlencode('Employee deleted successfully.')));
            exit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            header('Location: ' . onyx_legacy_url('human_resources.php?error=' . urlencode('Unable to delete employee.')));
            exit();
        }
    }
}

$employees = onyx_rows(
    'SELECT e.*, COALESCE(d.document_count, 0) AS document_count
     FROM hr_employees e
     LEFT JOIN (
        SELECT tenant_id, employee_id, COUNT(*) AS document_count
        FROM hr_employee_documents
        WHERE tenant_id = :document_tenant_id
        GROUP BY tenant_id, employee_id
     ) d ON d.employee_id = e.id AND d.tenant_id = e.tenant_id
     WHERE e.tenant_id = :tenant_id
     ORDER BY e.id DESC',
    ['document_tenant_id' => $tenant_id, 'tenant_id' => $tenant_id]
);

$activeCount = count(array_filter($employees, static fn (array $row): bool => ($row['status'] ?? '') === 'Active'));
$departmentCount = count(array_unique(array_filter(array_map(static fn (array $row): string => (string) ($row['department'] ?? ''), $employees))));
$documentCount = array_sum(array_map(static fn (array $row): int => (int) ($row['document_count'] ?? 0), $employees));
?>

<style>
    .hr-register,.hr-register *{border-radius:0!important}.hr-register{display:grid;gap:14px}.hr-hero{align-items:center;background:var(--onyx-surface);border:1px solid var(--onyx-border);display:flex;gap:12px;justify-content:space-between;padding:10px 14px}.hr-hero-title{align-items:center;display:flex;gap:10px;min-width:0}.hr-hero-icon{align-items:center;background:#fff;color:#050506;display:flex;flex:0 0 30px;font-size:11px;height:30px;justify-content:center;width:30px}.hr-hero h2{color:#fff;font-size:16px;font-weight:900;line-height:1.1;margin:0}.hr-hero p{color:var(--onyx-muted);font-size:10px;font-weight:600;line-height:1.25;margin:2px 0 0}.hr-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font-size:10px;font-weight:900;gap:7px;min-height:30px;padding:0 10px;text-decoration:none;text-transform:uppercase}.hr-btn.primary{background:#fff;color:#050506}.hr-btn.danger{border-color:rgba(255,138,138,.34);color:#ff8a8a}.hr-stats{display:grid;gap:8px;grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:max-content}.hr-stat{align-self:start;background:var(--onyx-surface);border:1px solid var(--onyx-border);min-height:42px;padding:7px 9px}.hr-stat span{color:var(--onyx-muted);display:block;font-size:8px;font-weight:900;line-height:1.1;text-transform:uppercase}.hr-stat strong{color:#fff;display:block;font-size:13px;font-weight:900;line-height:1;margin-top:3px}.hr-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:14px;overflow:hidden}.hr-panel-head{align-items:center;border-bottom:1px solid rgba(255,255,255,.07);display:flex;gap:10px;justify-content:space-between;margin-bottom:12px;padding-bottom:10px}.hr-panel-head h3{color:#fff;font-size:12px;font-weight:900;margin:0;text-transform:uppercase}.hr-muted{color:var(--onyx-muted);display:block;font-size:9px;font-weight:700;line-height:1.3;margin-top:3px}.hr-table-wrap{max-width:calc(100vw - 340px);overflow-x:auto}.hr-table{border-collapse:collapse;table-layout:fixed;width:1280px}.hr-table th,.hr-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:8px;text-align:left;vertical-align:top}.hr-table th{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.hr-employee-name strong{color:#fff;display:block;font-size:11px}.hr-status{border:1px solid rgba(255,255,255,.12);display:inline-flex;font-size:9px;font-weight:900;padding:4px 7px;text-transform:uppercase}.hr-status.ok{color:#8ff0c3}.hr-status.warn{color:#ffd27a}.hr-actions{align-items:center;display:flex;flex-wrap:nowrap;gap:6px}.hr-actions form{display:inline}.hr-alert{border:1px solid rgba(143,240,195,.26);color:#8ff0c3;font-size:11px;font-weight:800;padding:10px 12px}.hr-alert.error{border-color:rgba(255,138,138,.34);color:#ff8a8a}.hr-empty{border:1px solid rgba(255,255,255,.08);color:var(--onyx-muted);font-size:11px;font-weight:700;padding:14px}@media(max-width:1180px){.hr-stats{grid-template-columns:repeat(2,1fr)}.hr-table-wrap{max-width:calc(100vw - 36px)}}@media(max-width:680px){.hr-hero{align-items:flex-start;flex-direction:column}.hr-stats{grid-template-columns:1fr}.hr-btn{justify-content:center;width:100%}}
</style>

<div class="hr-register">
    <?php if (! empty($_GET['success'])): ?><div class="hr-alert"><?= hr_register_h($_GET['success']) ?></div><?php endif; ?>
    <?php if (! empty($_GET['error'])): ?><div class="hr-alert error"><?= hr_register_h($_GET['error']) ?></div><?php endif; ?>

    <section class="hr-hero">
        <div class="hr-hero-title">
            <div class="hr-hero-icon"><i class="fa-solid fa-users"></i></div>
            <div>
                <h2>Employees</h2>
                <p>Manage employee records, profile files, document status, and payroll-ready data.</p>
            </div>
        </div>
        <a class="hr-btn primary" href="<?= hr_register_h(onyx_legacy_url('hr_profiles.php?action=create')) ?>"><i class="fa-solid fa-user-plus"></i> Add Employee</a>
    </section>

    <section class="hr-stats">
        <div class="hr-stat"><span>Total Employees</span><strong><?= hr_register_h(count($employees)) ?></strong></div>
        <div class="hr-stat"><span>Active</span><strong><?= hr_register_h($activeCount) ?></strong></div>
        <div class="hr-stat"><span>Departments</span><strong><?= hr_register_h($departmentCount) ?></strong></div>
        <div class="hr-stat"><span>Documents</span><strong><?= hr_register_h($documentCount) ?></strong></div>
    </section>

    <section class="hr-panel">
        <div class="hr-panel-head">
            <div>
                <h3>Employee Register</h3>
                <span class="hr-muted">Use actions to view the profile, update details, or remove an employee.</span>
            </div>
            <a class="hr-btn" href="<?= hr_register_h(onyx_legacy_url('hr_documents.php')) ?>"><i class="fa-solid fa-folder-open"></i> Documents</a>
        </div>

        <?php if (empty($employees)): ?>
            <div class="hr-empty">No employees found. Add the first employee to start the HR register.</div>
        <?php else: ?>
            <div class="hr-table-wrap">
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th style="width:90px">Code</th>
                            <th style="width:210px">Employee</th>
                            <th style="width:140px">Department</th>
                            <th style="width:150px">Role</th>
                            <th style="width:180px">Contact</th>
                            <th style="width:90px">Docs</th>
                            <th style="width:110px">Basic Pay</th>
                            <th style="width:100px">Status</th>
                            <th style="width:260px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?= hr_register_h($employee['employee_code']) ?></td>
                                <td class="hr-employee-name"><strong><?= hr_register_h($employee['full_name']) ?></strong><span class="hr-muted"><?= hr_register_h($employee['employment_type'] ?: 'Full time') ?></span></td>
                                <td><?= hr_register_h($employee['department'] ?: '-') ?></td>
                                <td><?= hr_register_h($employee['job_title'] ?: '-') ?></td>
                                <td><?= hr_register_h($employee['phone'] ?: '-') ?><span class="hr-muted"><?= hr_register_h($employee['email'] ?: 'No email') ?></span></td>
                                <td><?= hr_register_h($employee['document_count']) ?></td>
                                <td><?= hr_register_h(onyx_money((float) $employee['basic_pay'], $currency)) ?></td>
                                <td><span class="hr-status <?= ($employee['status'] ?? '') === 'Active' ? 'ok' : 'warn' ?>"><?= hr_register_h($employee['status']) ?></span></td>
                                <td>
                                    <div class="hr-actions">
                                        <a class="hr-btn primary" href="<?= hr_register_h(onyx_legacy_url('hr_employee.php?id=' . (int) $employee['id'])) ?>"><i class="fa-solid fa-eye"></i> View Profile</a>
                                        <a class="hr-btn" href="<?= hr_register_h(onyx_legacy_url('hr_profiles.php?edit=' . (int) $employee['id'])) ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                                        <form method="post" onsubmit="return confirm('Delete this employee and attached document records?');">
                                            <input type="hidden" name="action" value="delete_employee">
                                            <input type="hidden" name="id" value="<?= hr_register_h($employee['id']) ?>">
                                            <button class="hr-btn danger" type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php onyx_page_end(); ?>
