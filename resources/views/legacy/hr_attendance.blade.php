<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . onyx_legacy_url('hr_attendance.php?success=' . urlencode('Attendance record captured for review.')));
    exit;
}

$context = onyx_page_start('Attendance', 'Clock-in, overtime, absence, late arrival, shift planning, and attendance approvals.');
$currency = $context['currency'];

$attendanceRows = [
    ['EMP-001', 'Super Admin', date('Y-m-d'), '08:00', '17:00', 'Present'],
    ['EMP-002', 'Cashier', date('Y-m-d'), '08:14', '17:00', 'Late'],
    ['EMP-003', 'Store Manager', date('Y-m-d'), '-', '-', 'Absent review'],
];
$shiftRows = [
    ['Sales Counter', '08:00', '17:00', 'Cashier', 'Active'],
    ['Store Control', '08:30', '17:30', 'Store Manager', 'Active'],
];
?>

<div class="ops-board">
    <?php if (! empty($_GET['success'])): ?><div class="ops-card" style="color:#8ff0c3;"><?= htmlspecialchars((string) $_GET['success']) ?></div><?php endif; ?>
    <div class="ops-strip">
        <div class="ops-card"><span>Present Today</span><strong>2</strong></div>
        <div class="ops-card"><span>Late Arrivals</span><strong>1</strong></div>
        <div class="ops-card"><span>Absent Review</span><strong>1</strong></div>
        <div class="ops-card"><span>Overtime Hours</span><strong>0</strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Attendance Capture', 'fa-clock', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee</label><select><option>Super Admin</option><option>Cashier</option><option>Store Manager</option></select></div>
                <div class="ops-field"><label>Date</label><input type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></div>
                <div class="ops-field"><label>Clock In</label><input type="time"></div>
                <div class="ops-field"><label>Clock Out</label><input type="time"></div>
                <div class="ops-field"><label>Status</label><select><option>Present</option><option>Late</option><option>Absent</option><option>Half day</option><option>Remote</option></select></div>
                <div class="ops-field"><label>Overtime</label><input type="number" step="0.25" placeholder="0"></div>
                <div class="ops-field wide"><label>Reason / Approval Note</label><input type="text" placeholder="Reason for late, absence, or overtime"></div>
                <button class="ops-btn" type="submit">Record Attendance</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Daily Attendance Register', 'fa-table-list', 'span-8'); ?>
            <?php onyx_table(['Code', 'Employee', 'Date', 'In', 'Out', 'Status'], $attendanceRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Shift Planner', 'fa-calendar-days', 'span-4'); ?>
            <?php onyx_table(['Shift', 'Start', 'End', 'Owner', 'Status'], $shiftRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
