<?php
$context = onyx_page_start('Leave Management', 'Annual, sick, maternity, unpaid leave, balances, requests, approvals, and return-to-work tracking.');
$currency = $context['currency'];

$leaveRows = [
    ['EMP-002', 'Cashier', 'Annual leave', date('Y-m-d', strtotime('+7 days')), '3 days', 'Pending'],
    ['EMP-003', 'Store Manager', 'Sick leave', date('Y-m-d'), '1 day', 'Draft'],
];
$balanceRows = [
    ['EMP-001', 'Super Admin', 'Annual', '21', '0', '21'],
    ['EMP-002', 'Cashier', 'Annual', '21', '3', '18'],
    ['EMP-003', 'Store Manager', 'Annual', '21', '0', '21'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Open Requests</span><strong><?= htmlspecialchars((string) count($leaveRows)) ?></strong></div>
        <div class="ops-card"><span>Approved Days</span><strong>0</strong></div>
        <div class="ops-card"><span>Pending Days</span><strong>4</strong></div>
        <div class="ops-card"><span>Return Checks</span><strong>0</strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Leave Request', 'fa-calendar-check', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee</label><select><option>Super Admin</option><option>Cashier</option><option>Store Manager</option></select></div>
                <div class="ops-field"><label>Leave Type</label><select><option>Annual leave</option><option>Sick leave</option><option>Maternity leave</option><option>Paternity leave</option><option>Unpaid leave</option></select></div>
                <div class="ops-field"><label>Start Date</label><input type="date"></div>
                <div class="ops-field"><label>End Date</label><input type="date"></div>
                <div class="ops-field"><label>Days</label><input type="number" step="0.5" placeholder="0"></div>
                <div class="ops-field"><label>Approver</label><input type="text" placeholder="Supervisor / HR"></div>
                <div class="ops-field wide"><label>Reason</label><input type="text" placeholder="Leave reason"></div>
                <button class="ops-btn" type="button">Submit Request</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Leave Approval Queue', 'fa-list-check', 'span-8'); ?>
            <?php onyx_table(['Code', 'Employee', 'Type', 'Start', 'Duration', 'Status'], $leaveRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Leave Balances', 'fa-scale-balanced', 'span-4'); ?>
            <?php onyx_table(['Code', 'Employee', 'Type', 'Entitled', 'Used', 'Balance'], $balanceRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
