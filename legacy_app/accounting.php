<?php
require_once __DIR__ . '/includes/erp_layout.php';
$context = onyx_page_start('Accounting', 'Chart of accounts, journal entries, and ledgers.');
$tenant_id = onyx_tenant_id();
?>

<div class="module-grid">
    <?php onyx_panel_start('Accounting Overview', 'fa-calculator', 'span-12'); ?>
        <p class="muted">Placeholder for Accounting configuration and reports.</p>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
