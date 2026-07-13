<?php
$context = onyx_page_start('Contracts & Roles', 'Employment type, grade, department, supervisor, contract terms, probation, and role control.');
$currency = $context['currency'];

$contracts = [
    ['EMP-001', 'Super Admin', 'Permanent', 'Administration', 'Director', 'Active'],
    ['EMP-002', 'Cashier', 'Full time', 'Sales', 'Sales Manager', 'Active'],
    ['EMP-003', 'Store Manager', 'Probation', 'Inventory', 'Operations Manager', 'Review due'],
];
$roleRows = [
    ['Administrator', 'Full system access', 'Super Admin', 'Active'],
    ['Cashier', 'POS, receipts, customers', 'Sales', 'Active'],
    ['Store Manager', 'Products, stock, purchases', 'Inventory', 'Active'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Active Contracts</span><strong>3</strong></div>
        <div class="ops-card"><span>Probation Reviews</span><strong>1</strong></div>
        <div class="ops-card"><span>Role Changes</span><strong>0</strong></div>
        <div class="ops-card"><span>Expiring Soon</span><strong>0</strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Contract Setup', 'fa-file-signature', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee</label><select><option>Super Admin</option><option>Cashier</option><option>Store Manager</option></select></div>
                <div class="ops-field"><label>Employment Type</label><select><option>Permanent</option><option>Full time</option><option>Part time</option><option>Contract</option><option>Casual</option></select></div>
                <div class="ops-field"><label>Department</label><select><option>Administration</option><option>Sales</option><option>Inventory</option><option>Finance</option></select></div>
                <div class="ops-field"><label>Job Grade</label><input type="text" placeholder="Grade / level"></div>
                <div class="ops-field"><label>Supervisor</label><input type="text" placeholder="Supervisor name"></div>
                <div class="ops-field"><label>Start Date</label><input type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></div>
                <div class="ops-field"><label>End Date</label><input type="date"></div>
                <div class="ops-field"><label>Probation End</label><input type="date"></div>
                <div class="ops-field full"><label>Role Summary</label><textarea rows="2" placeholder="Duties, reporting line, access needs, contract notes"></textarea></div>
                <button class="ops-btn" type="button">Save Contract</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Contract Register', 'fa-briefcase', 'span-8'); ?>
            <?php onyx_table(['Code', 'Employee', 'Type', 'Department', 'Supervisor', 'Status'], $contracts); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Role Catalogue', 'fa-user-shield', 'span-4'); ?>
            <?php onyx_table(['Role', 'Access Scope', 'Department', 'Status'], $roleRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
