<?php
$context = onyx_page_start('HR Documents', 'Contracts, IDs, certificates, results, medical forms, disciplinary records, acknowledgements, and file expiry control.');
$tenant_id = onyx_tenant_id();
$pdo = onyx_db();
onyx_hr_ensure_schema($pdo);
onyx_hr_seed_employees($pdo, $tenant_id);

function hr_docs_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$employees = onyx_rows('SELECT id, employee_code, full_name FROM hr_employees WHERE tenant_id = :tenant_id ORDER BY employee_code ASC', ['tenant_id' => $tenant_id]);
$documents = onyx_rows(
    'SELECT d.*, e.employee_code, e.full_name
     FROM hr_employee_documents d
     INNER JOIN hr_employees e ON e.id = d.employee_id AND e.tenant_id = d.tenant_id
     WHERE d.tenant_id = :tenant_id
     ORDER BY d.created_at DESC, d.id DESC',
    ['tenant_id' => $tenant_id]
);
$pending = count(array_filter($documents, static fn (array $row): bool => ($row['status'] ?? '') === 'Pending review'));
$expiring = count(array_filter($documents, static fn (array $row): bool => ! empty($row['expiry_date']) && strtotime((string) $row['expiry_date']) <= strtotime('+45 days')));
$missing = max(0, (count($employees) * 2) - count($documents));
?>

<style>
    .hr-doc-actions{display:flex;flex-wrap:wrap;gap:8px}.hr-doc-btn{align-items:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:#fff;display:inline-flex;font-size:10px;font-weight:800;gap:7px;min-height:32px;padding:0 10px;text-decoration:none;text-transform:uppercase}.hr-doc-btn.primary{background:#fff;color:#050506}.hr-doc-status{border:1px solid rgba(255,255,255,.12);display:inline-flex;font-size:9px;font-weight:800;padding:5px 8px;text-transform:uppercase}.hr-doc-status.ok{color:#8ff0c3}.hr-doc-status.warn{color:#ffd27a}.hr-doc-status.bad{color:#ff8a8a}
</style>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Documents Filed</span><strong><?= hr_docs_h(count($documents)) ?></strong></div>
        <div class="ops-card"><span>Pending Review</span><strong><?= hr_docs_h($pending) ?></strong></div>
        <div class="ops-card"><span>Expiring Soon</span><strong><?= hr_docs_h($expiring) ?></strong></div>
        <div class="ops-card"><span>Suggested Missing</span><strong><?= hr_docs_h($missing) ?></strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Document Capture', 'fa-folder-open', 'span-12'); ?>
            <form class="ops-form" method="post" enctype="multipart/form-data" action="<?= hr_docs_h(onyx_legacy_url('hr_employee.php')) ?>">
                <input type="hidden" name="action" value="upload_document">
                <div class="ops-field"><label>Employee</label><select name="employee_id"><?php foreach ($employees as $employee): ?><option value="<?= hr_docs_h($employee['id']) ?>"><?= hr_docs_h($employee['employee_code'] . ' - ' . $employee['full_name']) ?></option><?php endforeach; ?></select></div>
                <div class="ops-field"><label>Document Type</label><select name="document_type"><option>National ID</option><option>Academic results</option><option>Certificate</option><option>Employment contract</option><option>Medical form</option><option>Disciplinary record</option><option>Policy acknowledgement</option><option>Other</option></select></div>
                <div class="ops-field"><label>Title</label><input name="title" placeholder="Document title"></div>
                <div class="ops-field"><label>Document Ref</label><input name="document_ref" placeholder="Reference number"></div>
                <div class="ops-field"><label>Issued By</label><input name="issued_by" placeholder="Institution / authority"></div>
                <div class="ops-field"><label>Issue Date</label><input name="issue_date" type="date" value="<?= hr_docs_h(date('Y-m-d')) ?>"></div>
                <div class="ops-field"><label>Expiry Date</label><input name="expiry_date" type="date"></div>
                <div class="ops-field"><label>Status</label><select name="status"><option>Filed</option><option>Pending review</option><option>Verified</option><option>Expired</option></select></div>
                <div class="ops-field wide"><label>Upload File</label><input name="document_file" type="file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"></div>
                <div class="ops-field full"><label>Notes</label><textarea name="notes" rows="2" placeholder="Storage, expiry, verification, or physical file notes"></textarea></div>
                <button class="ops-btn" type="submit"><i class="fa-solid fa-upload"></i> Save Document</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Document Index', 'fa-file-lines', 'span-12'); ?>
            <?php
            onyx_table_html(['Employee', 'Document', 'Type', 'Issue', 'Expiry', 'Status', 'Action'], array_map(static function (array $document): array {
                $statusClass = match ($document['status'] ?? '') {
                    'Verified' => 'ok',
                    'Expired' => 'bad',
                    default => 'warn',
                };

                return [
                    hr_docs_h($document['employee_code'] . ' - ' . $document['full_name']),
                    hr_docs_h($document['title']),
                    hr_docs_h($document['document_type']),
                    hr_docs_h($document['issue_date'] ?: '-'),
                    hr_docs_h($document['expiry_date'] ?: '-'),
                    ['raw' => true, 'value' => '<span class="hr-doc-status ' . $statusClass . '">' . hr_docs_h($document['status']) . '</span>'],
                    ['raw' => true, 'value' => '<div class="hr-doc-actions"><a class="hr-doc-btn primary" href="' . hr_docs_h(onyx_legacy_url('hr_employee.php?id=' . (int) $document['employee_id'])) . '"><i class="fa-solid fa-user"></i> Profile</a>' . (! empty($document['file_path']) ? '<a class="hr-doc-btn" target="_blank" href="' . hr_docs_h(onyx_legacy_url('hr_employee.php?action=view_document&document_id=' . (int) $document['id'])) . '"><i class="fa-solid fa-eye"></i> View</a>' : '') . '</div>'],
                ];
            }, $documents));
            ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
