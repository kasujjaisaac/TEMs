<?php

$pdo = onyx_db();

if (! function_exists('customer_page_h')) {
    function customer_page_h(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('customer_page_ensure_columns')) {
    function customer_page_ensure_columns(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query('SHOW COLUMNS FROM customers')->fetchAll(PDO::FETCH_ASSOC) as $column) {
            $columns[] = $column['Field'];
        }

        if (! in_array('company_name', $columns, true)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN company_name VARCHAR(255) DEFAULT NULL");
        }
        if (! in_array('contact_person', $columns, true)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN contact_person VARCHAR(255) DEFAULT NULL");
        }
        if (! in_array('customer_group', $columns, true)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN customer_group VARCHAR(50) DEFAULT 'retail'");
        }
        if (! in_array('commercial_organization_id', $columns, true)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN commercial_organization_id BIGINT(20) DEFAULT NULL");
        }
        if (! in_array('commercial_reference', $columns, true)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN commercial_reference VARCHAR(80) DEFAULT NULL");
        }
        if (! in_array('commercial_sync_status', $columns, true)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN commercial_sync_status VARCHAR(60) DEFAULT NULL");
        }
        if (! in_array('commercial_synced_at', $columns, true)) {
            $pdo->exec("ALTER TABLE customers ADD COLUMN commercial_synced_at DATETIME DEFAULT NULL");
        }
    }
}

customer_page_ensure_columns($pdo);

$context = onyx_page_start('Customers', 'Registered customer accounts, profiles, statements, and account actions.');
$currency = $context['currency'];
$tenant_id = (int) (onyx_tenant_id() ?? 0);

$customers = onyx_rows(
    'SELECT id, commercial_organization_id, commercial_reference, commercial_sync_status, commercial_synced_at,
            customer_code, name, company_name, contact_person, customer_group, customer_source, phone, email,
            credit_limit, credit_balance, is_active, created_at
     FROM customers
     WHERE tenant_id = :tenant_id
     ORDER BY name ASC',
    ['tenant_id' => $tenant_id]
);
?>

<style>
    .customer-register-page,
    .customer-register-page * {
        border-radius: 0 !important;
    }

    .customer-register-page {
        display: grid;
        gap: 18px;
    }

    .customer-panel {
        background: var(--onyx-surface);
        border: 1px solid var(--onyx-border);
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
        padding: 18px;
    }

    .customer-toolbar {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-start;
    }

    .customer-action {
        align-items: center;
        background: rgba(255,255,255,0.035);
        border: 1px solid rgba(255,255,255,0.09);
        color: #fff;
        display: inline-flex;
        font-size: 0.68rem;
        font-weight: 800;
        gap: 8px;
        min-height: 38px;
        padding: 0 12px;
        text-decoration: none;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .customer-action.primary {
        background: #fff;
        color: #050506;
    }

    .customer-action.danger {
        color: #ff8a8a;
    }

    .customer-section-title {
        color: var(--onyx-muted);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        line-height: 1.45;
        text-transform: uppercase;
    }

    .customer-table-wrap {
        max-width: calc(100vw - 340px);
        margin-top: 14px;
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 16px;
        scrollbar-color: rgba(255,255,255,0.28) rgba(255,255,255,0.06);
        scrollbar-width: thin;
        width: 100%;
    }

    .customer-table-wrap::-webkit-scrollbar {
        height: 10px;
    }

    .customer-table-wrap::-webkit-scrollbar-track {
        background: rgba(255,255,255,0.06);
    }

    .customer-table-wrap::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.28);
    }

    .customer-table {
        border-collapse: collapse;
        table-layout: fixed;
        width: 1220px;
    }

    .customer-table th,
    .customer-table td {
        border-bottom: 1px solid rgba(255,255,255,0.06);
        font-size: 0.68rem;
        padding: 9px 9px;
        text-align: left;
        vertical-align: middle;
    }

    .customer-table th {
        color: var(--onyx-muted);
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.8px;
        text-transform: uppercase;
    }

    .customer-table tbody tr:hover {
        background: rgba(255,255,255,0.045);
    }

    .customer-name strong {
        color: #fff;
        display: block;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .customer-name span,
    .customer-muted {
        color: var(--onyx-muted);
        display: block;
        font-size: 0.62rem;
        margin-top: 3px;
    }

    .customer-badge {
        border: 1px solid rgba(255,255,255,0.12);
        color: #d8d8de;
        display: inline-flex;
        font-size: 0.56rem;
        font-weight: 800;
        padding: 5px 8px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .customer-badge.ok {
        color: #8ff0c3;
    }

    .customer-badge.warn {
        color: #ffd27a;
    }

    .customer-badge.danger {
        color: #ff8a8a;
    }

    .customer-table-actions {
        align-items: center;
        display: flex;
        flex-wrap: nowrap;
        gap: 8px;
    }

    @media (max-width: 992px) {
        .customer-table-wrap {
            max-width: calc(100vw - 36px);
        }
    }

    .customer-empty {
        border: 1px solid rgba(255,255,255,0.08);
        color: var(--onyx-muted);
        padding: 16px;
    }
</style>

<div class="customer-register-page">
    <section class="customer-panel">
        <div class="customer-toolbar">
            <a class="customer-action primary" href="<?= customer_page_h(onyx_legacy_url('customers_action.php?action=add')) ?>">
                <i class="fa-solid fa-user-plus"></i>
                Add Customer
            </a>
        </div>
    </section>

    <section class="customer-panel">
        <div class="customer-section-title">Registered Customers</div>
        <div class="customer-table-wrap">
            <table class="customer-table">
                <colgroup>
                    <col style="width: 100px;">
                    <col style="width: 170px;">
                    <col style="width: 150px;">
                    <col style="width: 130px;">
                    <col style="width: 180px;">
                    <col style="width: 100px;">
                    <col style="width: 130px;">
                    <col style="width: 160px;">
                    <col style="width: 100px;">
                    <col style="width: 310px;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Customer</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Group</th>
                        <th>Origin</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customers === []): ?>
                        <tr>
                            <td colspan="10">
                                <div class="customer-empty">No registered customers yet.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <?php
                                $id = (int) $customer['id'];
                                $balance = (float) ($customer['credit_balance'] ?? 0);
                                $limit = (float) ($customer['credit_limit'] ?? 0);
                                $balanceClass = $balance > $limit && $limit > 0 ? 'danger' : ($balance > 0 ? 'warn' : 'ok');
                                $statusLabel = ((int) $customer['is_active']) === 1 ? 'Active' : 'Inactive';
                                $statusClass = ((int) $customer['is_active']) === 1 ? 'ok' : 'danger';
                                $profileUrl = onyx_legacy_url('customers_action.php?action=view&id=' . $id);
                                $editUrl = onyx_legacy_url('customers_action.php?action=edit&id=' . $id);
                                $statementUrl = onyx_legacy_url('customers_action.php?action=print&id=' . $id);
                                $deleteUrl = onyx_legacy_url('customers_action.php?action=delete&id=' . $id);
                                $commercialOrgId = (int) ($customer['commercial_organization_id'] ?? 0);
                                $commercialUrl = $commercialOrgId > 0 ? url('commercial/organizations/' . $commercialOrgId) : '';
                            ?>
                            <tr>
                                <td><?= customer_page_h($customer['customer_code'] ?: '-') ?></td>
                                <td>
                                    <div class="customer-name">
                                        <strong><?= customer_page_h($customer['name']) ?></strong>
                                        <span><?= customer_page_h($customer['contact_person'] ?: 'No contact person') ?></span>
                                    </div>
                                </td>
                                <td><?= customer_page_h($customer['company_name'] ?: '-') ?></td>
                                <td><?= customer_page_h($customer['phone'] ?: '-') ?></td>
                                <td><?= customer_page_h($customer['email'] ?: '-') ?></td>
                                <td><span class="customer-badge"><?= customer_page_h($customer['customer_group'] ?: 'retail') ?></span></td>
                                <td>
                                    <?php if ($commercialOrgId > 0): ?>
                                        <a class="customer-badge ok" href="<?= customer_page_h($commercialUrl) ?>"><?= customer_page_h($customer['commercial_reference'] ?: 'Commercial') ?></a>
                                        <span class="customer-muted"><?= customer_page_h($customer['commercial_sync_status'] ?: 'Synced') ?></span>
                                    <?php else: ?>
                                        <span class="customer-badge"><?= customer_page_h($customer['customer_source'] ?: 'Legacy') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="customer-badge <?= customer_page_h($balanceClass) ?>"><?= customer_page_h(onyx_money($balance, $currency)) ?></span>
                                    <span class="customer-muted">Limit <?= customer_page_h(onyx_money($limit, $currency)) ?></span>
                                </td>
                                <td><span class="customer-badge <?= customer_page_h($statusClass) ?>"><?= customer_page_h($statusLabel) ?></span></td>
                                <td>
                                    <div class="customer-table-actions">
                                        <a class="customer-action" href="<?= customer_page_h($profileUrl) ?>">Profile</a>
                                        <a class="customer-action" href="<?= customer_page_h($editUrl) ?>">Edit</a>
                                        <a class="customer-action" href="<?= customer_page_h($statementUrl) ?>" target="_blank">Statement</a>
                                        <?php if ($commercialOrgId > 0): ?><a class="customer-action" href="<?= customer_page_h($commercialUrl) ?>">Commercial</a><?php endif; ?>
                                        <a class="customer-action danger" href="<?= customer_page_h($deleteUrl) ?>">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php onyx_page_end(); ?>
