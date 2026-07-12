<?php
require_once __DIR__ . '/includes/erp_layout.php';
$context = onyx_page_start('Banking', 'Bank transactions, reconciliations, and accounts.');
$tenant_id = onyx_tenant_id();
?>

<div class="module-grid">
    <?php onyx_panel_start('Banking', 'fa-university', 'span-12'); ?>
        <p class="muted">Placeholder for bank accounts, transfers, and reconciliations.</p>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
