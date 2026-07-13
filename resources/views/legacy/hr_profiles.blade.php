<?php
$context = onyx_page_start('Employee Profiles', 'Bio data, contacts, next of kin, bank details, emergency contacts, and employee master records.');
$currency = $context['currency'];

$profiles = [
    ['EMP-001', 'Super Admin', 'Administration', 'superadmin@clinic.test', '+256 700 000 001', 'Complete'],
    ['EMP-002', 'Cashier', 'Sales', 'cashier@clinic.test', '+256 700 000 002', 'Bank pending'],
    ['EMP-003', 'Store Manager', 'Inventory', 'store@clinic.test', '+256 700 000 003', 'Next of kin pending'],
];
$emergencyRows = [
    ['EMP-002', 'Jane Cashier', 'Spouse', '+256 701 111 222', 'Primary'],
    ['EMP-003', 'Michael Store', 'Brother', '+256 702 222 333', 'Primary'],
];
?>

<div class="ops-board">
    <div class="ops-strip">
        <div class="ops-card"><span>Total Profiles</span><strong><?= htmlspecialchars((string) count($profiles)) ?></strong></div>
        <div class="ops-card"><span>Complete Files</span><strong>1</strong></div>
        <div class="ops-card"><span>Bank Pending</span><strong>1</strong></div>
        <div class="ops-card"><span>Emergency Pending</span><strong>1</strong></div>
    </div>

    <div class="module-grid">
        <?php onyx_panel_start('Profile Capture', 'fa-address-card', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Employee Code</label><input type="text" placeholder="EMP-000"></div>
                <div class="ops-field"><label>Full Name</label><input type="text" placeholder="Employee full name"></div>
                <div class="ops-field"><label>Gender</label><select><option>Female</option><option>Male</option><option>Prefer not to say</option></select></div>
                <div class="ops-field"><label>Date of Birth</label><input type="date"></div>
                <div class="ops-field"><label>Phone</label><input type="text" placeholder="Primary phone"></div>
                <div class="ops-field"><label>Email</label><input type="email" placeholder="Work or personal email"></div>
                <div class="ops-field"><label>National ID</label><input type="text" placeholder="NIN / ID number"></div>
                <div class="ops-field"><label>Bank / Wallet</label><input type="text" placeholder="Bank account or mobile money"></div>
                <div class="ops-field wide"><label>Residential Address</label><input type="text" placeholder="Village, parish, district"></div>
                <div class="ops-field"><label>Next of Kin</label><input type="text" placeholder="Name"></div>
                <div class="ops-field"><label>Kin Phone</label><input type="text" placeholder="Phone"></div>
                <button class="ops-btn" type="button">Save Profile</button>
                <button class="ops-btn ghost" type="button">Update Contact</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Employee Master Register', 'fa-users', 'span-8'); ?>
            <?php onyx_table(['Code', 'Name', 'Department', 'Email', 'Phone', 'File Status'], $profiles); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Emergency Contacts', 'fa-phone-volume', 'span-4'); ?>
            <?php onyx_table(['Code', 'Contact', 'Relation', 'Phone', 'Priority'], $emergencyRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
