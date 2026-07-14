<?php
$tenant_id = onyx_tenant_id();
$pdo = onyx_db();
onyx_hr_ensure_schema($pdo);
onyx_hr_seed_employees($pdo, $tenant_id);

function hr_employee_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'view_document') {
    $documentId = (int) ($_GET['document_id'] ?? 0);
    $document = onyx_row('SELECT * FROM hr_employee_documents WHERE id = :id AND tenant_id = :tenant_id', ['id' => $documentId, 'tenant_id' => $tenant_id]);
    if (! $document || empty($document['file_path'])) {
        abort(404, 'Document file not found.');
    }

    $base = storage_path('app/hr_employee_documents');
    $path = realpath($base . DIRECTORY_SEPARATOR . basename((string) $document['file_path']));
    if (! $path || ! str_starts_with($path, realpath($base))) {
        abort(404, 'Document file not found.');
    }

    $mime = $document['mime_type'] ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $document['original_name'] ?: $document['title']) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit();
}

$employeeId = (int) ($_GET['id'] ?? $_POST['employee_id'] ?? 0);
$employee = $employeeId > 0 ? onyx_row('SELECT * FROM hr_employees WHERE id = :id AND tenant_id = :tenant_id', ['id' => $employeeId, 'tenant_id' => $tenant_id]) : false;
if (! $employee) {
    $first = onyx_row('SELECT id FROM hr_employees WHERE tenant_id = :tenant_id ORDER BY employee_code ASC LIMIT 1', ['tenant_id' => $tenant_id]);
    if ($first) {
        header('Location: ' . onyx_legacy_url('hr_employee.php?id=' . (int) $first['id']));
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_document' && $employee) {
    require_permission('hr.manage');

    $title = trim($_POST['title'] ?? '');
    $documentType = trim($_POST['document_type'] ?? 'Other');
    $storedName = null;
    $originalName = null;
    $mimeType = null;
    $fileSize = null;

    if (isset($_FILES['document_file']) && is_uploaded_file($_FILES['document_file']['tmp_name'])) {
        if ((int) ($_FILES['document_file']['size'] ?? 0) > 10 * 1024 * 1024) {
            header('Location: ' . onyx_legacy_url('hr_employee.php?id=' . (int) $employee['id'] . '&error=' . urlencode('Document file cannot exceed 10MB.')));
            exit();
        }

        $uploadDir = storage_path('app/hr_employee_documents');
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }
        $originalName = $_FILES['document_file']['name'] ?? 'document';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx'];
        if (! in_array($extension, $allowedExtensions, true)) {
            header('Location: ' . onyx_legacy_url('hr_employee.php?id=' . (int) $employee['id'] . '&error=' . urlencode('Only PDF, image, Word, and Excel documents are allowed.')));
            exit();
        }

        $detectedMime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['document_file']['tmp_name']) ?: '';
        $allowedMimes = [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ];
        if (! in_array($detectedMime, $allowedMimes, true)) {
            header('Location: ' . onyx_legacy_url('hr_employee.php?id=' . (int) $employee['id'] . '&error=' . urlencode('The uploaded document type is not allowed.')));
            exit();
        }

        $storedName = 'tenant-' . $tenant_id . '-employee-' . (int) $employee['id'] . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        if (! move_uploaded_file($_FILES['document_file']['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $storedName)) {
            header('Location: ' . onyx_legacy_url('hr_employee.php?id=' . (int) $employee['id'] . '&error=' . urlencode('Unable to store the uploaded document.')));
            exit();
        }
        $mimeType = $detectedMime;
        $fileSize = $_FILES['document_file']['size'] ?? null;
    }

    if ($title !== '' || $storedName !== null) {
        $stmt = $pdo->prepare('INSERT INTO hr_employee_documents (tenant_id, employee_id, document_type, title, document_ref, issued_by, issue_date, expiry_date, file_path, original_name, mime_type, file_size, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenant_id,
            (int) $employee['id'],
            $documentType,
            $title !== '' ? $title : $documentType,
            trim($_POST['document_ref'] ?? ''),
            trim($_POST['issued_by'] ?? ''),
            $_POST['issue_date'] ?: null,
            $_POST['expiry_date'] ?: null,
            $storedName,
            $originalName,
            $mimeType,
            $fileSize,
            trim($_POST['status'] ?? 'Filed'),
            trim($_POST['notes'] ?? ''),
        ]);
    }

    header('Location: ' . onyx_legacy_url('hr_employee.php?id=' . (int) $employee['id'] . '&success=' . urlencode('Document saved successfully.')));
    exit();
}

$context = onyx_page_start('Employee Profile', 'Single employee dossier, document vault, identification records, certificates, results, and HR file status.');
$currency = $context['currency'];
$documents = $employee ? onyx_rows('SELECT * FROM hr_employee_documents WHERE tenant_id = :tenant_id AND employee_id = :employee_id ORDER BY created_at DESC, id DESC', ['tenant_id' => $tenant_id, 'employee_id' => (int) $employee['id']]) : [];
$contract = $employee ? onyx_row('SELECT * FROM hr_employee_contracts WHERE tenant_id = :tenant_id AND employee_id = :employee_id ORDER BY id DESC LIMIT 1', ['tenant_id' => $tenant_id, 'employee_id' => (int) $employee['id']]) : false;
$verified = count(array_filter($documents, static fn (array $row): bool => ($row['status'] ?? '') === 'Verified'));
$expiring = count(array_filter($documents, static fn (array $row): bool => ! empty($row['expiry_date']) && strtotime((string) $row['expiry_date']) <= strtotime('+45 days')));
?>

<style>
    .employee-file,.employee-file *{border-radius:0!important}.employee-file{display:grid;gap:14px}.employee-alert{border:1px solid rgba(143,240,195,.26);color:#8ff0c3;font-size:11px;font-weight:800;padding:10px 12px}.employee-alert.error{border-color:rgba(255,138,138,.34);color:#ff8a8a}.employee-hero{background:var(--onyx-surface);border:1px solid var(--onyx-border);display:grid;gap:14px;grid-template-columns:auto minmax(0,1fr) auto;padding:14px}.employee-avatar{align-items:center;background:#fff;color:#050506;display:flex;font-size:22px;font-weight:900;height:64px;justify-content:center;width:64px}.employee-name{color:#fff;font-size:18px;font-weight:900;line-height:1.1;margin:0}.employee-subline{color:var(--onyx-muted);font-size:10px;font-weight:800;margin-top:4px;text-transform:uppercase}.employee-meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}.employee-chip,.employee-pill{align-items:center;border:1px solid rgba(255,255,255,.12);color:#d8d8de;display:inline-flex;font-size:9px;font-weight:900;gap:6px;min-height:26px;padding:0 8px;text-transform:uppercase}.employee-chip.ok{color:#8ff0c3}.employee-chip.warn{color:#ffd27a}.employee-actions{align-items:center;display:flex;flex-wrap:wrap;gap:7px;justify-content:flex-end}.employee-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font-size:10px;font-weight:900;gap:7px;min-height:30px;padding:0 10px;text-decoration:none;text-transform:uppercase;white-space:nowrap}.employee-btn.primary{background:#fff;color:#050506}.employee-stats{display:grid;gap:8px;grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:max-content}.employee-stat{align-self:start;background:var(--onyx-surface);border:1px solid var(--onyx-border);min-height:42px;padding:7px 9px}.employee-stat span{color:var(--onyx-muted);display:block;font-size:8px;font-weight:900;line-height:1.1;text-transform:uppercase}.employee-stat strong{color:#fff;display:block;font-size:13px;font-weight:900;line-height:1;margin-top:3px}.employee-layout{display:grid;gap:14px;grid-template-columns:minmax(280px,420px) minmax(0,1fr)}.employee-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);padding:14px;overflow:hidden}.employee-panel.full{grid-column:1 / -1}.employee-panel-head{align-items:center;border-bottom:1px solid rgba(255,255,255,.07);display:flex;gap:10px;justify-content:space-between;margin-bottom:12px;padding-bottom:10px}.employee-panel-head h3{color:#fff;font-size:12px;font-weight:900;margin:0;text-transform:uppercase}.employee-muted{color:var(--onyx-muted);display:block;font-size:9px;font-weight:700;line-height:1.35;margin-top:3px}.employee-info-grid{display:grid;gap:8px}.employee-info{border:1px solid rgba(255,255,255,.08);padding:9px}.employee-info span{color:var(--onyx-muted);display:block;font-size:8px;font-weight:900;text-transform:uppercase}.employee-info strong{color:#fff;display:block;font-size:11px;font-weight:800;line-height:1.35;margin-top:4px;word-break:break-word}.employee-form-grid{display:grid;gap:10px;grid-template-columns:repeat(12,minmax(0,1fr))}.employee-field{display:grid;gap:5px;grid-column:span 4;min-width:0}.employee-field.wide{grid-column:span 8}.employee-field.full{grid-column:span 12}.employee-field label{color:var(--onyx-muted);font-size:9px;font-weight:900;text-transform:uppercase}.employee-field input,.employee-field select,.employee-field textarea{background:#101016;border:1px solid rgba(255,255,255,.12);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:36px;padding:8px 10px;width:100%}.employee-field textarea{min-height:74px;resize:vertical}.employee-field select option{background:#050506;color:#fff}.employee-form-actions{display:flex;justify-content:flex-end;margin-top:12px}.employee-doc-grid{display:grid;gap:10px;grid-template-columns:repeat(12,minmax(0,1fr))}.employee-doc-card{background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.08);display:grid;gap:9px;grid-column:span 4;padding:11px}.employee-doc-card.empty{grid-column:span 12}.employee-doc-card strong{color:#fff;font-size:11px;font-weight:900;line-height:1.25}.employee-doc-card span{color:var(--onyx-muted);font-size:9px;line-height:1.5}.employee-doc-footer{align-items:center;display:flex;flex-wrap:wrap;gap:7px;justify-content:space-between}.employee-status{border:1px solid rgba(255,255,255,.12);display:inline-flex;font-size:9px;font-weight:900;padding:4px 7px;text-transform:uppercase}.employee-status.ok{color:#8ff0c3}.employee-status.warn{color:#ffd27a}.employee-status.bad{color:#ff8a8a}@media(max-width:1100px){.employee-layout{grid-template-columns:1fr}.employee-stats{grid-template-columns:repeat(2,1fr)}.employee-doc-card{grid-column:span 6}}@media(max-width:760px){.employee-hero{grid-template-columns:1fr}.employee-actions{justify-content:flex-start}.employee-stats{grid-template-columns:1fr}.employee-field,.employee-field.wide{grid-column:span 12}.employee-doc-card{grid-column:span 12}.employee-btn{justify-content:center;width:100%}}
</style>

<div class="employee-file">
    <?php if (! $employee): ?>
        <div class="employee-alert error">No employee profiles found. Create one from the Employees page first.</div>
    <?php else: ?>
        <?php if (! empty($_GET['success'])): ?><div class="employee-alert"><?= hr_employee_h($_GET['success']) ?></div><?php endif; ?>
        <?php if (! empty($_GET['error'])): ?><div class="employee-alert error"><?= hr_employee_h($_GET['error']) ?></div><?php endif; ?>

        <section class="employee-hero">
            <div class="employee-avatar"><?= hr_employee_h(strtoupper(substr((string) $employee['full_name'], 0, 1))) ?></div>
            <div>
                <h2 class="employee-name"><?= hr_employee_h($employee['full_name']) ?></h2>
                <div class="employee-subline"><?= hr_employee_h($employee['employee_code']) ?> / <?= hr_employee_h($employee['employment_type'] ?: 'Employee') ?></div>
                <div class="employee-meta">
                    <span class="employee-chip <?= ($employee['status'] ?? '') === 'Active' ? 'ok' : 'warn' ?>"><i class="fa-solid fa-circle-check"></i> <?= hr_employee_h($employee['status'] ?: 'No status') ?></span>
                    <span class="employee-chip"><i class="fa-solid fa-building"></i> <?= hr_employee_h($employee['department'] ?: 'No department') ?></span>
                    <span class="employee-chip"><i class="fa-solid fa-briefcase"></i> <?= hr_employee_h($employee['job_title'] ?: 'No role') ?></span>
                    <span class="employee-chip"><i class="fa-solid fa-phone"></i> <?= hr_employee_h($employee['phone'] ?: 'No phone') ?></span>
                    <span class="employee-chip"><i class="fa-solid fa-envelope"></i> <?= hr_employee_h($employee['email'] ?: 'No email') ?></span>
                </div>
            </div>
            <div class="employee-actions">
                <a class="employee-btn" href="<?= hr_employee_h(onyx_legacy_url('hr_profiles.php?edit=' . (int) $employee['id'])) ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                <a class="employee-btn" href="<?= hr_employee_h(onyx_legacy_url('hr_contracts.php?action=create&employee_id=' . (int) $employee['id'])) ?>"><i class="fa-solid fa-file-signature"></i> Contract</a>
                <a class="employee-btn primary" href="<?= hr_employee_h(onyx_legacy_url('human_resources.php')) ?>"><i class="fa-solid fa-users"></i> Employees</a>
            </div>
        </section>

        <section class="employee-stats">
            <div class="employee-stat"><span>Documents</span><strong><?= hr_employee_h(count($documents)) ?></strong></div>
            <div class="employee-stat"><span>Verified</span><strong><?= hr_employee_h($verified) ?></strong></div>
            <div class="employee-stat"><span>Expiring Soon</span><strong><?= hr_employee_h($expiring) ?></strong></div>
            <div class="employee-stat"><span>Basic Pay</span><strong><?= hr_employee_h(onyx_money((float) $employee['basic_pay'], $currency)) ?></strong></div>
        </section>

        <div class="employee-layout">
            <aside class="employee-panel">
                <div class="employee-panel-head">
                    <div>
                        <h3>Profile Details</h3>
                        <span class="employee-muted">Identity, emergency contact, payroll, and contract snapshot.</span>
                    </div>
                </div>
                <div class="employee-info-grid">
                    <div class="employee-info"><span>National ID</span><strong><?= hr_employee_h($employee['national_id'] ?: '-') ?></strong></div>
                    <div class="employee-info"><span>Bank / Wallet</span><strong><?= hr_employee_h($employee['bank_wallet'] ?: '-') ?></strong></div>
                    <div class="employee-info"><span>Address</span><strong><?= hr_employee_h($employee['address'] ?: '-') ?></strong></div>
                    <div class="employee-info"><span>Next of Kin</span><strong><?= hr_employee_h(($employee['next_of_kin'] ?: '-') . ' / ' . ($employee['kin_phone'] ?: '-')) ?></strong></div>
                    <div class="employee-info"><span>Contract</span><strong><?= hr_employee_h($contract ? (($contract['contract_type'] ?: 'Contract') . ' / ' . ($contract['start_date'] ?: '-') . ' to ' . ($contract['end_date'] ?: 'Open')) : 'No contract assigned') ?></strong></div>
                    <div class="employee-info"><span>Notes</span><strong><?= hr_employee_h($employee['notes'] ?: '-') ?></strong></div>
                </div>
            </aside>

            <main class="employee-panel">
                <div class="employee-panel-head">
                    <div>
                        <h3>Add Important Document</h3>
                        <span class="employee-muted">Upload National ID, results, certificates, contracts, or other HR records.</span>
                    </div>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_document">
                    <input type="hidden" name="employee_id" value="<?= hr_employee_h($employee['id']) ?>">
                    <div class="employee-form-grid">
                        <div class="employee-field"><label>Document Type</label><select name="document_type"><option>National ID</option><option>Academic results</option><option>Certificate</option><option>Employment contract</option><option>Medical form</option><option>Disciplinary record</option><option>Policy acknowledgement</option><option>Other</option></select></div>
                        <div class="employee-field wide"><label>Title</label><input name="title" placeholder="Document title"></div>
                        <div class="employee-field"><label>Document Ref</label><input name="document_ref" placeholder="NIN, certificate no., ref"></div>
                        <div class="employee-field"><label>Issued By</label><input name="issued_by" placeholder="Institution / authority"></div>
                        <div class="employee-field"><label>Issue Date</label><input name="issue_date" type="date"></div>
                        <div class="employee-field"><label>Expiry Date</label><input name="expiry_date" type="date"></div>
                        <div class="employee-field"><label>Status</label><select name="status"><option>Filed</option><option>Pending review</option><option>Verified</option><option>Expired</option></select></div>
                        <div class="employee-field wide"><label>Upload File</label><input name="document_file" type="file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"></div>
                        <div class="employee-field full"><label>Notes</label><textarea name="notes" rows="2" placeholder="Review notes, storage remarks, expiry requirements"></textarea></div>
                    </div>
                    <div class="employee-form-actions"><button class="employee-btn primary" type="submit"><i class="fa-solid fa-upload"></i> Save Document</button></div>
                </form>
            </main>

            <section class="employee-panel full">
                <div class="employee-panel-head">
                    <div>
                        <h3>Document Vault</h3>
                        <span class="employee-muted">Filed employee documents and viewable uploaded records.</span>
                    </div>
                </div>
                <div class="employee-doc-grid">
                    <?php if ($documents === []): ?>
                        <div class="employee-doc-card empty"><strong>No documents filed</strong><span>Upload National ID, results, certificates, contracts, or other important HR files for this employee.</span></div>
                    <?php else: ?>
                        <?php foreach ($documents as $document): ?>
                            <?php $docStatus = strtolower((string) ($document['status'] ?? '')); ?>
                            <article class="employee-doc-card">
                                <strong><?= hr_employee_h($document['title']) ?></strong>
                                <span><?= hr_employee_h($document['document_type']) ?><br>Ref: <?= hr_employee_h($document['document_ref'] ?: '-') ?><br>Issued: <?= hr_employee_h($document['issue_date'] ?: '-') ?> / Expires: <?= hr_employee_h($document['expiry_date'] ?: '-') ?></span>
                                <div class="employee-doc-footer">
                                    <span class="employee-status <?= $docStatus === 'verified' ? 'ok' : ($docStatus === 'expired' ? 'bad' : 'warn') ?>"><?= hr_employee_h($document['status']) ?></span>
                                    <?php if (! empty($document['file_path'])): ?><a class="employee-btn primary" target="_blank" href="<?= hr_employee_h(onyx_legacy_url('hr_employee.php?action=view_document&document_id=' . (int) $document['id'])) ?>"><i class="fa-solid fa-eye"></i> View</a><?php endif; ?>
                                </div>
                                <span class="employee-pill"><i class="fa-solid fa-file-shield"></i> <?= hr_employee_h($document['original_name'] ?: 'Record only') ?></span>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</div>

<?php onyx_page_end(); ?>
