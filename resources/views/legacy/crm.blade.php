<?php

$context = onyx_page_start('CRM', 'Customer relationship management for leads, opportunities, activities, and sales reports.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();
$crm_contacts = array_map(
    static fn (array $row): array => [
        $row['name'],
        $row['customer_group'],
        $row['phone'] ?: '-',
        $row['email'] ?: '-',
        ((int) $row['is_active']) === 1 ? 'Active' : 'Inactive',
        'Operator',
    ],
    onyx_rows(
        'SELECT name, customer_group, phone, email, is_active
         FROM customers
         WHERE tenant_id = :tenant_id
         ORDER BY updated_at DESC, name ASC
         LIMIT 25',
        ['tenant_id' => $tenant_id]
    )
);
?>

<div class="module-grid">
    <?php onyx_panel_start('Leads', 'fa-address-card', 'span-12'); ?>
        <?php onyx_table(
            ['Contact Name', 'Group', 'Phone', 'Email', 'Status', 'Assigned Staff'],
            $crm_contacts
        ); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Opportunities', 'fa-bullseye', 'span-6'); ?>
        <?php onyx_table(
            ['Opportunity', 'Potential Value', 'Expected Closing Date', 'Probability'],
            [
                ['Monthly supply contract', '0.00 ' . $currency, 'Pending', '60%'],
                ['Equipment sale', '0.00 ' . $currency, 'Pending', '40%'],
                ['Service retainer', '0.00 ' . $currency, 'Pending', '75%'],
            ]
        ); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Activities', 'fa-list-check', 'span-6'); ?>
        <?php onyx_clean_list([
            ['Calls', '0 Scheduled'],
            ['Meetings', '0 Scheduled'],
            ['Emails', '0 Pending'],
            ['Follow-ups', '0 Due'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Reports', 'fa-chart-simple', 'span-12'); ?>
        <?php onyx_clean_list([
            ['Lead Conversion Rate', '0%'],
            ['Sales Pipeline', '0.00 ' . $currency],
            ['Top Sales Agents', 'No ranked agents'],
        ]); ?>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>
