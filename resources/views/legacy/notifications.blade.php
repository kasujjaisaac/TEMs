<?php
$context = onyx_page_start('Notifications', 'Manage system email and alerts.');
$tenant_id = onyx_tenant_id();
?>

<div class="module-grid">
    <?php onyx_panel_start('Notifications', 'fa-bell', 'span-12'); ?>
        <p class="muted">Placeholder for notification preferences and delivery history.</p>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
