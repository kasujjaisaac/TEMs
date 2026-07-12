<?php
$context = onyx_page_start('Reports', 'Financial and operational reports.');
$tenant_id = onyx_tenant_id();
?>

<div class="module-grid">
    <?php onyx_panel_start('Reports', 'fa-chart-line', 'span-12'); ?>
        <p class="muted">Placeholder for report builder and scheduled reports.</p>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
