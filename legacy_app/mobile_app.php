<?php
require_once __DIR__ . '/includes/erp_layout.php';
$context = onyx_page_start('Mobile App', 'Mobile app settings and API access.');
$tenant_id = onyx_tenant_id();
?>

<div class="module-grid">
    <?php onyx_panel_start('Mobile App', 'fa-mobile-alt', 'span-12'); ?>
        <p class="muted">Placeholder for mobile app configuration, API keys, and links.</p>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
