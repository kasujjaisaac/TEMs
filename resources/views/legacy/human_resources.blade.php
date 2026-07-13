<?php
$context = onyx_page_start('Human Resource', 'Employee records, contracts, attendance, leave, advances, appraisals, documents, and payroll readiness.');
$currency = $context['currency'];

$employees = [
    ['EMP-001', 'Super Admin', 'Administration', 'Super Admin', 'Active', onyx_money(0, $currency)],
    ['EMP-002', 'Cashier', 'Sales', 'Cashier', 'Active', onyx_money(0, $currency)],
    ['EMP-003', 'Store Manager', 'Inventory', 'Store Manager', 'Onboarding', onyx_money(0, $currency)],
];
$leaveRows = [
    ['EMP-002', 'Annual leave', date('Y-m-d', strtotime('+7 days')), '3 days', 'Pending'],
    ['EMP-003', 'Sick leave', date('Y-m-d'), '1 day', 'Draft'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Employees</span><strong><?= htmlspecialchars((string) count($employees)) ?></strong></div>
        <div class="ops-card"><span>Departments</span><strong>3</strong></div>
        <div class="ops-card"><span>Leave Requests</span><strong><?= htmlspecialchars((string) count($leaveRows)) ?></strong></div>
        <div class="ops-card"><span>Payroll Ready</span><strong>0</strong></div>
    </div>

    <div class="ops-actions">
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('hr_profiles.php')) ?>"><i class="fa-solid fa-address-card"></i><span>Profiles</span></a>
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('hr_contracts.php')) ?>"><i class="fa-solid fa-file-signature"></i><span>Contracts</span></a>
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('hr_attendance.php')) ?>"><i class="fa-solid fa-clock"></i><span>Attendance</span></a>
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('hr_leave.php')) ?>"><i class="fa-solid fa-calendar-check"></i><span>Leave</span></a>
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('hr_advances.php')) ?>"><i class="fa-solid fa-hand-holding-dollar"></i><span>Advances</span></a>
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('hr_documents.php')) ?>"><i class="fa-solid fa-folder-open"></i><span>Documents</span></a>
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('hr_performance.php')) ?>"><i class="fa-solid fa-chart-line"></i><span>Performance</span></a>
        <a class="ops-action" href="<?= htmlspecialchars(onyx_legacy_url('hr_payroll_readiness.php')) ?>"><i class="fa-solid fa-clipboard-check"></i><span>Payroll Readiness</span></a>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Employee Register', 'fa-id-card-clip', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee Name</label><input type="text" placeholder="Full name"></div>
                <div class="ops-field"><label>Department</label><select><option>Administration</option><option>Sales</option><option>Inventory</option><option>Finance</option></select></div>
                <div class="ops-field"><label>Job Title</label><input type="text" placeholder="Role / position"></div>
                <div class="ops-field"><label>Employment Type</label><select><option>Full time</option><option>Part time</option><option>Contract</option><option>Casual</option></select></div>
                <div class="ops-field"><label>Phone</label><input type="text" placeholder="Phone"></div>
                <div class="ops-field"><label>Email</label><input type="email" placeholder="Email"></div>
                <div class="ops-field"><label>Basic Pay</label><input type="number" step="0.01" placeholder="0.00"></div>
                <div class="ops-field"><label>Status</label><select><option>Active</option><option>Onboarding</option><option>Suspended</option><option>Exited</option></select></div>
                <div class="ops-field full"><label>Notes</label><textarea rows="2" placeholder="Contract, bank, next-of-kin, document notes"></textarea></div>
                <button class="ops-btn" type="button">Save Employee</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Employee List', 'fa-users', 'span-12'); ?>
            <?php onyx_table(['Code', 'Name', 'Department', 'Role', 'Status', 'Basic Pay'], $employees); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Leave and Attendance Queue', 'fa-calendar-check', 'span-12'); ?>
            <?php onyx_table(['Employee', 'Type', 'Date', 'Duration', 'Status'], $leaveRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
