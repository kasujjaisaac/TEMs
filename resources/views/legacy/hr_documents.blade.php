<?php
$context = onyx_page_start('HR Documents', 'Contracts, IDs, certificates, medical forms, disciplinary records, acknowledgements, and file expiry control.');
$currency = $context['currency'];

$documentRows = [
    ['EMP-001', 'Employment contract', 'Contract', date('Y-m-d'), 'Filed'],
    ['EMP-002', 'National ID', 'Identity', date('Y-m-d'), 'Verified'],
    ['EMP-003', 'Academic certificate', 'Certificate', date('Y-m-d'), 'Pending review'],
];
$expiryRows = [
    ['EMP-003', 'Probation letter', date('Y-m-d', strtotime('+30 days')), 'Review'],
    ['EMP-002', 'Medical form', date('Y-m-d', strtotime('+60 days')), 'Renewal due'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Documents Filed</span><strong><?= htmlspecialchars((string) count($documentRows)) ?></strong></div>
        <div class="ops-card"><span>Pending Review</span><strong>1</strong></div>
        <div class="ops-card"><span>Expiring Soon</span><strong>2</strong></div>
        <div class="ops-card"><span>Missing Files</span><strong>0</strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Document Register', 'fa-folder-open', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee</label><select><option>Super Admin</option><option>Cashier</option><option>Store Manager</option></select></div>
                <div class="ops-field"><label>Document Type</label><select><option>Employment contract</option><option>National ID</option><option>Certificate</option><option>Disciplinary record</option><option>Medical form</option><option>Policy acknowledgement</option></select></div>
                <div class="ops-field"><label>Document Ref</label><input type="text" placeholder="Reference number"></div>
                <div class="ops-field"><label>Issue Date</label><input type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></div>
                <div class="ops-field"><label>Expiry Date</label><input type="date"></div>
                <div class="ops-field"><label>Status</label><select><option>Filed</option><option>Pending review</option><option>Verified</option><option>Expired</option></select></div>
                <div class="ops-field wide"><label>Storage Location</label><input type="text" placeholder="Digital path or physical file"></div>
                <button class="ops-btn" type="button">Save Document</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Document Index', 'fa-file-lines', 'span-8'); ?>
            <?php onyx_table(['Code', 'Document', 'Type', 'Date', 'Status'], $documentRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Expiry and Review Queue', 'fa-hourglass-half', 'span-4'); ?>
            <?php onyx_table(['Code', 'Document', 'Due Date', 'Action'], $expiryRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
