<?php
$context = onyx_page_start('Performance', 'Appraisals, KPIs, training, confirmations, disciplinary actions, and performance improvement plans.');
$currency = $context['currency'];

$reviewRows = [
    ['EMP-001', 'Super Admin', 'Quarterly review', date('Y-m-d', strtotime('+14 days')), 'Scheduled'],
    ['EMP-002', 'Cashier', 'Probation confirmation', date('Y-m-d', strtotime('+21 days')), 'Pending'],
    ['EMP-003', 'Store Manager', 'KPI review', date('Y-m-d', strtotime('+30 days')), 'Draft'],
];
$trainingRows = [
    ['POS controls', 'Cashier', date('Y-m-d', strtotime('+10 days')), 'Planned'],
    ['Inventory controls', 'Store Manager', date('Y-m-d', strtotime('+15 days')), 'Planned'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Reviews Due</span><strong>3</strong></div>
        <div class="ops-card"><span>Training Plans</span><strong>2</strong></div>
        <div class="ops-card"><span>Confirmations</span><strong>1</strong></div>
        <div class="ops-card"><span>Improvement Plans</span><strong>0</strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Performance Review', 'fa-chart-line', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee</label><select><option>Super Admin</option><option>Cashier</option><option>Store Manager</option></select></div>
                <div class="ops-field"><label>Review Type</label><select><option>Quarterly review</option><option>Annual appraisal</option><option>Probation confirmation</option><option>Disciplinary review</option><option>Improvement plan</option></select></div>
                <div class="ops-field"><label>Review Date</label><input type="date"></div>
                <div class="ops-field"><label>Reviewer</label><input type="text" placeholder="Supervisor / HR"></div>
                <div class="ops-field"><label>KPI Score</label><input type="number" step="0.1" placeholder="0"></div>
                <div class="ops-field"><label>Outcome</label><select><option>Scheduled</option><option>Meets expectation</option><option>Needs improvement</option><option>Confirmed</option></select></div>
                <div class="ops-field wide"><label>Action Plan</label><input type="text" placeholder="Training, targets, follow-up actions"></div>
                <button class="ops-btn" type="button">Save Review</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Review Calendar', 'fa-calendar-days', 'span-8'); ?>
            <?php onyx_table(['Code', 'Employee', 'Review', 'Due Date', 'Status'], $reviewRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Training Plan', 'fa-graduation-cap', 'span-4'); ?>
            <?php onyx_table(['Training', 'Owner', 'Date', 'Status'], $trainingRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
