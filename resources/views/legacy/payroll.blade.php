<?php
$context = onyx_page_start('Payroll', 'Employee payroll, payslips, and deductions.');
$tenant_id = onyx_tenant_id();
?>

<div class="module-grid">
    <?php onyx_panel_start('Payroll', 'fa-money-bill-wave', 'span-12'); ?>
        <p class="muted">Placeholder for payroll processing and employee payments.</p>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
