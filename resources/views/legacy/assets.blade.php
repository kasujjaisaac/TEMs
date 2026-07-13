<?php
$context = onyx_page_start('Assets', 'Fixed assets register, depreciation, custody, maintenance, insurance, and disposals.');
$currency = $context['currency'];
$assetRows = [
    ['AST-001', 'Office computers', 'IT Equipment', onyx_money(4500000, $currency), 'Straight line', 'In use'],
    ['AST-002', 'Delivery motorcycle', 'Motor Vehicle', onyx_money(6200000, $currency), 'Reducing balance', 'In use'],
    ['AST-003', 'Shop fittings', 'Furniture', onyx_money(2800000, $currency), 'Straight line', 'Insured'],
];
$maintenanceRows = [
    ['AST-002', 'Service and inspection', date('Y-m-d'), onyx_money(0, $currency), 'Scheduled'],
    ['AST-001', 'Warranty review', date('Y-m-d', strtotime('+14 days')), onyx_money(0, $currency), 'Pending'],
];
?>

<div class="ops-board">
    <div class="module-grid">
        <?php onyx_panel_start('Asset Register Form', 'fa-laptop-file', 'span-12'); ?>
            <form class="ops-form" method="post">
                <div class="ops-field"><label>Asset Name</label><input type="text" placeholder="Asset name"></div>
                <div class="ops-field"><label>Category</label><select><option>IT Equipment</option><option>Motor Vehicle</option><option>Furniture</option><option>Machinery</option><option>Building</option></select></div>
                <div class="ops-field"><label>Purchase Cost</label><input type="number" step="0.01" placeholder="0.00"></div>
                <div class="ops-field"><label>Purchase Date</label><input type="date" value="<?= htmlspecialchars(date('Y-m-d')) ?>"></div>
                <div class="ops-field"><label>Custodian</label><input type="text" placeholder="Employee or department"></div>
                <div class="ops-field"><label>Location</label><input type="text" placeholder="Branch / room / site"></div>
                <div class="ops-field"><label>Depreciation</label><select><option>Straight line</option><option>Reducing balance</option><option>Manual</option></select></div>
                <div class="ops-field"><label>Status</label><select><option>In use</option><option>Under maintenance</option><option>Disposed</option></select></div>
                <div class="ops-field full"><label>Notes</label><textarea rows="2" placeholder="Serial number, warranty, insurance, disposal notes"></textarea></div>
                <button class="ops-btn" type="button">Register Asset</button>
            </form>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Fixed Asset Register', 'fa-boxes-stacked', 'span-8'); ?>
            <?php onyx_table(['Code', 'Asset', 'Category', 'Cost', 'Depreciation', 'Status'], $assetRows); ?>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Asset Controls', 'fa-shield-halved', 'span-4'); ?>
            <div class="ops-tags">
                <span>Custody assignment</span>
                <span>Depreciation schedule</span>
                <span>Insurance tracking</span>
                <span>Maintenance plan</span>
                <span>Asset verification</span>
                <span>Disposal approval</span>
            </div>
        <?php onyx_panel_end(); ?>

        <?php onyx_panel_start('Maintenance and Warranty Queue', 'fa-screwdriver-wrench', 'span-12'); ?>
            <?php onyx_table(['Asset', 'Task', 'Due Date', 'Expected Cost', 'Status'], $maintenanceRows); ?>
        <?php onyx_panel_end(); ?>
    </div>
</div>

<?php onyx_page_end(); ?>
