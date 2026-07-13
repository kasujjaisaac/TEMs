<?php

$pdo = onyx_db();
$tenant_id = (int) onyx_tenant_id();

function crm_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function crm_redirect(string $message = '', bool $success = true): void
{
    $query = $message !== '' ? ($success ? '?success=' : '?error=') . urlencode($message) : '';
    header('Location: crm.php' . $query);
    exit();
}

function crm_columns(PDO $pdo, string $table): array
{
    try {
        return array_map(static fn (array $row): string => $row['Field'], $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable) {
        return [];
    }
}

function crm_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (! in_array($column, crm_columns($pdo, $table), true)) {
        $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
    }
}

function crm_ensure_tables(PDO $pdo): void
{
    crm_ensure_column($pdo, 'customers', 'company_name', 'VARCHAR(255) DEFAULT NULL');
    crm_ensure_column($pdo, 'customers', 'contact_person', 'VARCHAR(255) DEFAULT NULL');
    crm_ensure_column($pdo, 'customers', 'customer_source', 'VARCHAR(80) DEFAULT NULL');
    crm_ensure_column($pdo, 'customers', 'account_manager', 'VARCHAR(155) DEFAULT NULL');
    crm_ensure_column($pdo, 'customers', 'internal_notes', 'TEXT DEFAULT NULL');

    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_leads (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        customer_id BIGINT(20) DEFAULT NULL,
        contact_name VARCHAR(155) NOT NULL,
        company_name VARCHAR(255) DEFAULT NULL,
        phone VARCHAR(40) DEFAULT NULL,
        email VARCHAR(191) DEFAULT NULL,
        source VARCHAR(80) DEFAULT 'walk_in',
        status VARCHAR(40) DEFAULT 'new',
        priority VARCHAR(30) DEFAULT 'normal',
        assigned_to VARCHAR(155) DEFAULT NULL,
        estimated_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        expected_close_date DATE DEFAULT NULL,
        converted_customer_id BIGINT(20) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_crm_leads_tenant_status (tenant_id, status),
        KEY idx_crm_leads_tenant_customer (tenant_id, customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_opportunities (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        lead_id BIGINT(20) DEFAULT NULL,
        customer_id BIGINT(20) DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        stage VARCHAR(40) DEFAULT 'qualification',
        value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        probability INT NOT NULL DEFAULT 25,
        expected_close_date DATE DEFAULT NULL,
        owner VARCHAR(155) DEFAULT NULL,
        status VARCHAR(40) DEFAULT 'open',
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_crm_opportunities_tenant_stage (tenant_id, stage),
        KEY idx_crm_opportunities_tenant_customer (tenant_id, customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_activities (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        lead_id BIGINT(20) DEFAULT NULL,
        opportunity_id BIGINT(20) DEFAULT NULL,
        customer_id BIGINT(20) DEFAULT NULL,
        activity_type VARCHAR(40) DEFAULT 'follow_up',
        subject VARCHAR(255) NOT NULL,
        due_at DATETIME DEFAULT NULL,
        completed_at DATETIME DEFAULT NULL,
        status VARCHAR(40) DEFAULT 'open',
        priority VARCHAR(30) DEFAULT 'normal',
        assigned_to VARCHAR(155) DEFAULT NULL,
        outcome VARCHAR(255) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_crm_activities_tenant_due (tenant_id, status, due_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS crm_campaigns (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        tenant_id BIGINT(20) NOT NULL,
        name VARCHAR(255) NOT NULL,
        channel VARCHAR(80) DEFAULT 'field_sales',
        budget DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        status VARCHAR(40) DEFAULT 'active',
        notes TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_crm_campaigns_tenant_status (tenant_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}

function crm_badge(string $label, string $class = ''): string
{
    return '<span class="crm-badge ' . crm_h($class) . '">' . crm_h(ucwords(str_replace('_', ' ', $label))) . '</span>';
}

function crm_money(mixed $amount, string $currency): string
{
    return number_format((float) ($amount ?? 0), 2) . ' ' . $currency;
}

function crm_customer_code(PDO $pdo, int $tenantId): string
{
    do {
        $code = 'CUS-' . strtoupper(substr(uniqid(), -7));
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE tenant_id = ? AND customer_code = ?');
        $stmt->execute([$tenantId, $code]);
    } while ((int) $stmt->fetchColumn() > 0);

    return $code;
}

function crm_stage_probability(string $stage, int $fallback): int
{
    return match ($stage) {
        'qualification' => 20,
        'proposal' => 45,
        'negotiation' => 70,
        'won' => 100,
        'lost' => 0,
        default => max(0, min(100, $fallback)),
    };
}

function crm_select_options(array $options, ?string $current): void
{
    foreach ($options as $value => $label) {
        echo '<option value="' . crm_h($value) . '"' . ((string) $current === (string) $value ? ' selected' : '') . '>' . crm_h($label) . '</option>';
    }
}

crm_ensure_tables($pdo);

$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_lead') {
        $contact = trim($_POST['contact_name'] ?? '');
        if ($contact === '') {
            crm_redirect('Lead contact name is required.', false);
        }

        $stmt = $pdo->prepare('INSERT INTO crm_leads (tenant_id, customer_id, contact_name, company_name, phone, email, source, status, priority, assigned_to, estimated_value, expected_close_date, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenant_id,
            (int) ($_POST['customer_id'] ?? 0) ?: null,
            $contact,
            trim($_POST['company_name'] ?? '') ?: null,
            trim($_POST['phone'] ?? '') ?: null,
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['source'] ?? 'walk_in') ?: 'walk_in',
            trim($_POST['status'] ?? 'new') ?: 'new',
            trim($_POST['priority'] ?? 'normal') ?: 'normal',
            trim($_POST['assigned_to'] ?? '') ?: session('user_name', 'Operator'),
            max(0, (float) ($_POST['estimated_value'] ?? 0)),
            ($_POST['expected_close_date'] ?? '') ?: null,
            trim($_POST['notes'] ?? '') ?: null,
        ]);
        crm_redirect('Lead captured successfully.');
    }

    if ($action === 'update_lead_status') {
        $id = (int) ($_POST['lead_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'contacted');
        $stmt = $pdo->prepare('UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$status, $id, $tenant_id]);
        crm_redirect('Lead status updated.');
    }

    if ($action === 'convert_lead') {
        $id = (int) ($_POST['lead_id'] ?? 0);
        $lead = onyx_row('SELECT * FROM crm_leads WHERE id = :id AND tenant_id = :tenant_id', ['id' => $id, 'tenant_id' => $tenant_id]);
        if (! $lead) {
            crm_redirect('Lead not found.', false);
        }
        if ((int) ($lead['converted_customer_id'] ?? 0) > 0) {
            crm_redirect('This lead has already been converted.', false);
        }

        $customerId = (int) ($lead['customer_id'] ?? 0);
        if ($customerId <= 0) {
            $code = crm_customer_code($pdo, $tenant_id);
            $stmt = $pdo->prepare('INSERT INTO customers (tenant_id, customer_code, name, company_name, contact_person, email, phone, customer_group, customer_source, account_manager, internal_notes, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())');
            $stmt->execute([
                $tenant_id,
                $code,
                $lead['company_name'] ?: $lead['contact_name'],
                $lead['company_name'] ?: null,
                $lead['contact_name'],
                $lead['email'] ?: null,
                $lead['phone'] ?: null,
                'corporate',
                $lead['source'] ?: 'crm',
                $lead['assigned_to'] ?: session('user_name', 'Operator'),
                $lead['notes'] ?: null,
            ]);
            $customerId = (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare('UPDATE crm_leads SET status = ?, converted_customer_id = ?, customer_id = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute(['converted', $customerId, $customerId, $id, $tenant_id]);
        crm_redirect('Lead converted to customer.');
    }

    if ($action === 'add_opportunity') {
        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            crm_redirect('Opportunity title is required.', false);
        }
        $stage = trim($_POST['stage'] ?? 'qualification') ?: 'qualification';
        $probability = crm_stage_probability($stage, (int) ($_POST['probability'] ?? 25));
        $stmt = $pdo->prepare('INSERT INTO crm_opportunities (tenant_id, lead_id, customer_id, title, stage, value, probability, expected_close_date, owner, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenant_id,
            (int) ($_POST['lead_id'] ?? 0) ?: null,
            (int) ($_POST['customer_id'] ?? 0) ?: null,
            $title,
            $stage,
            max(0, (float) ($_POST['value'] ?? 0)),
            $probability,
            ($_POST['expected_close_date'] ?? '') ?: null,
            trim($_POST['owner'] ?? '') ?: session('user_name', 'Operator'),
            in_array($stage, ['won', 'lost'], true) ? $stage : 'open',
            trim($_POST['notes'] ?? '') ?: null,
        ]);
        crm_redirect('Opportunity added to pipeline.');
    }

    if ($action === 'update_opportunity_stage') {
        $id = (int) ($_POST['opportunity_id'] ?? 0);
        $stage = trim($_POST['stage'] ?? 'qualification') ?: 'qualification';
        $status = in_array($stage, ['won', 'lost'], true) ? $stage : 'open';
        $probability = crm_stage_probability($stage, (int) ($_POST['probability'] ?? 25));
        $stmt = $pdo->prepare('UPDATE crm_opportunities SET stage = ?, probability = ?, status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute([$stage, $probability, $status, $id, $tenant_id]);
        crm_redirect('Opportunity stage updated.');
    }

    if ($action === 'add_activity') {
        $subject = trim($_POST['subject'] ?? '');
        if ($subject === '') {
            crm_redirect('Activity subject is required.', false);
        }
        $dueDate = trim($_POST['due_date'] ?? '');
        $dueTime = trim($_POST['due_time'] ?? '09:00');
        $dueAt = $dueDate !== '' ? $dueDate . ' ' . ($dueTime ?: '09:00') . ':00' : null;
        $stmt = $pdo->prepare('INSERT INTO crm_activities (tenant_id, lead_id, opportunity_id, customer_id, activity_type, subject, due_at, status, priority, assigned_to, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenant_id,
            (int) ($_POST['lead_id'] ?? 0) ?: null,
            (int) ($_POST['opportunity_id'] ?? 0) ?: null,
            (int) ($_POST['customer_id'] ?? 0) ?: null,
            trim($_POST['activity_type'] ?? 'follow_up') ?: 'follow_up',
            $subject,
            $dueAt,
            'open',
            trim($_POST['priority'] ?? 'normal') ?: 'normal',
            trim($_POST['assigned_to'] ?? '') ?: session('user_name', 'Operator'),
            trim($_POST['notes'] ?? '') ?: null,
        ]);
        crm_redirect('Activity scheduled.');
    }

    if ($action === 'complete_activity') {
        $id = (int) ($_POST['activity_id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE crm_activities SET status = ?, completed_at = NOW(), outcome = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?');
        $stmt->execute(['completed', trim($_POST['outcome'] ?? '') ?: 'Completed', $id, $tenant_id]);
        crm_redirect('Activity completed.');
    }

    if ($action === 'add_campaign') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            crm_redirect('Campaign name is required.', false);
        }
        $stmt = $pdo->prepare('INSERT INTO crm_campaigns (tenant_id, name, channel, budget, start_date, end_date, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $tenant_id,
            $name,
            trim($_POST['channel'] ?? 'field_sales') ?: 'field_sales',
            max(0, (float) ($_POST['budget'] ?? 0)),
            ($_POST['start_date'] ?? '') ?: null,
            ($_POST['end_date'] ?? '') ?: null,
            trim($_POST['status'] ?? 'active') ?: 'active',
            trim($_POST['notes'] ?? '') ?: null,
        ]);
        crm_redirect('Campaign added.');
    }

    crm_redirect('Unsupported CRM action.', false);
}

$context = onyx_page_start('CRM', 'Leads, pipeline, follow-ups, campaigns, and customer relationship execution.');
$currency = $context['currency'];

$customers = onyx_rows(
    'SELECT id, name, company_name, contact_person, phone, email, customer_group, account_manager, is_active
     FROM customers WHERE tenant_id = :tenant_id ORDER BY updated_at DESC, name ASC LIMIT 100',
    ['tenant_id' => $tenant_id]
);

$leads = onyx_rows(
    'SELECT l.*, c.name AS customer_name
     FROM crm_leads l
     LEFT JOIN customers c ON c.id = l.customer_id AND c.tenant_id = l.tenant_id
     WHERE l.tenant_id = :tenant_id
     ORDER BY FIELD(l.status, "new", "contacted", "qualified", "proposal", "converted", "lost"), l.updated_at DESC
     LIMIT 80',
    ['tenant_id' => $tenant_id]
);

$opportunities = onyx_rows(
    'SELECT o.*, c.name AS customer_name, l.contact_name AS lead_name
     FROM crm_opportunities o
     LEFT JOIN customers c ON c.id = o.customer_id AND c.tenant_id = o.tenant_id
     LEFT JOIN crm_leads l ON l.id = o.lead_id AND l.tenant_id = o.tenant_id
     WHERE o.tenant_id = :tenant_id
     ORDER BY FIELD(o.stage, "qualification", "proposal", "negotiation", "won", "lost"), o.expected_close_date IS NULL, o.expected_close_date ASC, o.updated_at DESC
     LIMIT 80',
    ['tenant_id' => $tenant_id]
);

$activities = onyx_rows(
    'SELECT a.*, c.name AS customer_name, l.contact_name AS lead_name, o.title AS opportunity_title
     FROM crm_activities a
     LEFT JOIN customers c ON c.id = a.customer_id AND c.tenant_id = a.tenant_id
     LEFT JOIN crm_leads l ON l.id = a.lead_id AND l.tenant_id = a.tenant_id
     LEFT JOIN crm_opportunities o ON o.id = a.opportunity_id AND o.tenant_id = a.tenant_id
     WHERE a.tenant_id = :tenant_id
     ORDER BY FIELD(a.status, "open", "completed"), a.due_at IS NULL, a.due_at ASC, a.created_at DESC
     LIMIT 100',
    ['tenant_id' => $tenant_id]
);

$campaigns = onyx_rows('SELECT * FROM crm_campaigns WHERE tenant_id = :tenant_id ORDER BY status ASC, start_date DESC, created_at DESC LIMIT 40', ['tenant_id' => $tenant_id]);

$leadCount = count($leads);
$openLeadCount = count(array_filter($leads, static fn (array $lead): bool => ! in_array($lead['status'], ['converted', 'lost'], true)));
$convertedLeadCount = count(array_filter($leads, static fn (array $lead): bool => $lead['status'] === 'converted'));
$openPipeline = array_sum(array_map(static fn (array $opp): float => $opp['status'] === 'open' ? (float) $opp['value'] : 0.0, $opportunities));
$weightedPipeline = array_sum(array_map(static fn (array $opp): float => $opp['status'] === 'open' ? (float) $opp['value'] * ((int) $opp['probability'] / 100) : 0.0, $opportunities));
$dueActivities = count(array_filter($activities, static fn (array $item): bool => $item['status'] === 'open'));
$overdueActivities = count(array_filter($activities, static fn (array $item): bool => $item['status'] === 'open' && ! empty($item['due_at']) && strtotime($item['due_at']) < time()));
$conversionRate = $leadCount > 0 ? round(($convertedLeadCount / $leadCount) * 100) : 0;
?>

<style>
    .crm-page,.crm-page *{border-radius:0!important}
    .crm-page{display:grid;gap:16px}
    .crm-panel{background:var(--onyx-surface);border:1px solid var(--onyx-border);overflow:hidden;padding:18px}
    .crm-grid{display:grid;gap:16px;grid-template-columns:repeat(12,minmax(0,1fr))}
    .crm-span-3{grid-column:span 3}.crm-span-4{grid-column:span 4}.crm-span-5{grid-column:span 5}.crm-span-6{grid-column:span 6}.crm-span-7{grid-column:span 7}.crm-span-8{grid-column:span 8}.crm-span-12{grid-column:span 12}
    .crm-register-panel,.crm-entry-panel{grid-column:span 12}.crm-register-panel{order:1}.crm-entry-panel{order:2}
    .crm-title{align-items:center;color:#fff;display:flex;font-size:10px;font-weight:800;gap:9px;margin-bottom:14px;text-transform:uppercase}
    .crm-toolbar,.crm-actions,.crm-inline{align-items:center;display:flex;flex-wrap:wrap;gap:8px}.crm-toolbar{justify-content:space-between}.crm-actions{justify-content:flex-end}
    .crm-btn,.crm-inline button{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;cursor:pointer;display:inline-flex;font:inherit;font-size:10px;font-weight:800;gap:8px;min-height:36px;padding:0 11px;text-decoration:none;text-transform:uppercase}
    .crm-btn.primary,.crm-inline button.primary{background:#fff;color:#050506}.crm-btn.danger{color:#ff8a8a}
    .crm-kpi{border:1px solid rgba(255,255,255,.08);display:grid;gap:8px;padding:14px}.crm-kpi span{color:var(--onyx-muted);font-size:10px;font-weight:800;text-transform:uppercase}.crm-kpi strong{color:#fff;font-size:19px}.crm-kpi em{color:var(--onyx-muted);font-size:10px;font-style:normal}
    .crm-form{display:grid;gap:12px;grid-template-columns:repeat(12,minmax(0,1fr))}
    .crm-field{display:grid;gap:6px;grid-column:span 4;min-width:0}.crm-field.wide{grid-column:span 8}.crm-field.full{grid-column:span 12}
    .crm-compact-form .crm-field{grid-column:span 2}.crm-compact-form .crm-field.medium{grid-column:span 3}.crm-compact-form .crm-field.wide{grid-column:span 4}.crm-compact-form .crm-field.full{grid-column:span 6}.crm-compact-form textarea{min-height:38px}
    .crm-lead-entry{order:1}.crm-lead-register{order:2}
    .crm-field label{color:var(--onyx-muted);font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase}
    .crm-field input,.crm-field select,.crm-field textarea,.crm-inline select,.crm-inline input{background:rgba(0,0,0,.18);border:1px solid rgba(255,255,255,.1);color:#fff;font-family:Poppins,system-ui,sans-serif;font-size:10px;min-height:36px;outline:0;padding:8px 10px;width:100%}
    .crm-field textarea{min-height:76px;resize:vertical}
    .crm-table-wrap{overflow-x:auto;padding-bottom:12px}.crm-table{border-collapse:collapse;min-width:980px;width:100%}
    .crm-table th,.crm-table td{border-bottom:1px solid rgba(255,255,255,.06);font-size:10px;padding:9px;text-align:left;vertical-align:top}.crm-table th{color:var(--onyx-muted);font-size:9px;font-weight:800;text-transform:uppercase}
    .crm-name strong{color:#fff;display:block;font-size:11px}.crm-name span,.crm-muted{color:var(--onyx-muted);display:block;font-size:10px;margin-top:3px}
    .crm-badge{border:1px solid rgba(255,255,255,.12);color:#d8d8de;display:inline-flex;font-size:9px;font-weight:800;padding:5px 8px;text-transform:uppercase;white-space:nowrap}.crm-badge.ok{color:#8ff0c3}.crm-badge.warn{color:#ffd27a}.crm-badge.danger{color:#ff8a8a}.crm-badge.info{color:#9fd7ff}
    .crm-tabs{align-items:center;background:rgba(0,0,0,.12);border:1px solid rgba(255,255,255,.08);display:flex;flex-wrap:wrap;gap:8px;padding:8px;position:sticky;top:70px;z-index:20}
    .crm-tab{background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.09);color:#fff;cursor:pointer;font:inherit;font-size:10px;font-weight:800;min-height:36px;padding:0 12px;text-transform:uppercase}.crm-tab.active{background:#fff;color:#050506}
    .crm-tab-panel{display:none}.crm-tab-panel.active{display:grid;gap:16px}
    .crm-pipeline{display:grid;gap:10px;grid-template-columns:repeat(5,minmax(190px,1fr));overflow-x:auto;padding-bottom:8px}.crm-stage{border:1px solid rgba(255,255,255,.08);display:grid;gap:10px;min-width:190px;padding:12px}.crm-stage h3{color:var(--onyx-muted);font-size:10px;margin:0;text-transform:uppercase}.crm-deal{border:1px solid rgba(255,255,255,.08);display:grid;gap:6px;padding:10px}.crm-deal strong{font-size:11px}.crm-deal span{color:var(--onyx-muted);font-size:10px}
    .crm-alert{border:1px solid rgba(143,240,195,.24);color:#8ff0c3;font-size:11px;font-weight:700;padding:11px 12px}.crm-alert.error{border-color:rgba(255,138,138,.28);color:#ff8a8a}
    @media(max-width:1100px){.crm-span-3,.crm-span-4,.crm-span-5,.crm-span-6,.crm-span-7,.crm-span-8{grid-column:span 12}.crm-field,.crm-field.wide{grid-column:span 6}}
    @media(max-width:680px){.crm-field,.crm-field.wide{grid-column:span 12}.crm-actions{justify-content:stretch}.crm-btn{justify-content:center;width:100%}.crm-tabs{position:static}}
</style>

<div class="crm-page">
    <?php if (isset($_GET['success'])): ?><div class="crm-alert"><?= crm_h($_GET['success']) ?></div><?php endif; ?>
    <?php if (isset($_GET['error'])): ?><div class="crm-alert error"><?= crm_h($_GET['error']) ?></div><?php endif; ?>

    <section class="crm-panel">
        <div class="crm-toolbar">
            <div class="crm-title" style="margin-bottom:0;"><i class="fa-solid fa-handshake"></i> CRM Command Center</div>
            <div class="crm-inline">
                <a class="crm-btn" href="<?= crm_h(onyx_legacy_url('customers.php')) ?>"><i class="fa-solid fa-users"></i> Customers</a>
                <a class="crm-btn" href="<?= crm_h(onyx_legacy_url('sales.php')) ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Sales</a>
            </div>
        </div>
    </section>

    <section class="crm-grid">
        <div class="crm-kpi crm-span-3"><span>Open Leads</span><strong><?= crm_h($openLeadCount) ?></strong><em><?= crm_h($leadCount) ?> total lead records</em></div>
        <div class="crm-kpi crm-span-3"><span>Conversion</span><strong><?= crm_h($conversionRate) ?>%</strong><em><?= crm_h($convertedLeadCount) ?> converted leads</em></div>
        <div class="crm-kpi crm-span-3"><span>Open Pipeline</span><strong><?= crm_h(crm_money($openPipeline, $currency)) ?></strong><em>Weighted <?= crm_h(crm_money($weightedPipeline, $currency)) ?></em></div>
        <div class="crm-kpi crm-span-3"><span>Activities Due</span><strong><?= crm_h($dueActivities) ?></strong><em><?= crm_h($overdueActivities) ?> overdue</em></div>
    </section>

    <section class="crm-tabs" data-crm-tabs>
        <button class="crm-tab active" type="button" data-tab="overview">Overview</button>
        <button class="crm-tab" type="button" data-tab="leads">Leads</button>
        <button class="crm-tab" type="button" data-tab="pipeline">Pipeline</button>
        <button class="crm-tab" type="button" data-tab="activities">Activities</button>
        <button class="crm-tab" type="button" data-tab="campaigns">Campaigns</button>
    </section>

    <div class="crm-tab-panel active" data-panel="overview">
        <section class="crm-grid">
            <div class="crm-panel crm-span-12">
                <div class="crm-title"><i class="fa-solid fa-gauge-high"></i> Pipeline Board</div>
                <div class="crm-pipeline">
                    <?php foreach (['qualification' => 'Qualification', 'proposal' => 'Proposal', 'negotiation' => 'Negotiation', 'won' => 'Won', 'lost' => 'Lost'] as $stage => $label): ?>
                        <div class="crm-stage">
                            <h3><?= crm_h($label) ?></h3>
                            <?php $stageItems = array_values(array_filter($opportunities, static fn (array $opp): bool => $opp['stage'] === $stage)); ?>
                            <?php if ($stageItems === []): ?>
                                <span class="crm-muted">No opportunities</span>
                            <?php else: ?>
                                <?php foreach (array_slice($stageItems, 0, 5) as $opp): ?>
                                    <div class="crm-deal">
                                        <strong><?= crm_h($opp['title']) ?></strong>
                                        <span><?= crm_h($opp['customer_name'] ?: $opp['lead_name'] ?: 'Unlinked') ?></span>
                                        <span><?= crm_h(crm_money($opp['value'], $currency)) ?> · <?= crm_h($opp['probability']) ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="crm-panel crm-span-12">
                <div class="crm-title"><i class="fa-solid fa-clock"></i> Next Actions</div>
                <div class="crm-table-wrap">
                    <table class="crm-table" style="min-width:520px;">
                        <thead><tr><th>Task</th><th>Due</th><th>Owner</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php $openActivities = array_values(array_filter($activities, static fn (array $item): bool => $item['status'] === 'open')); ?>
                        <?php if ($openActivities === []): ?>
                            <tr><td colspan="4" class="crm-muted">No open CRM activities.</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($openActivities, 0, 8) as $activity): ?>
                                <tr>
                                    <td><div class="crm-name"><strong><?= crm_h($activity['subject']) ?></strong><span><?= crm_h($activity['customer_name'] ?: $activity['lead_name'] ?: $activity['opportunity_title'] ?: '-') ?></span></div></td>
                                    <td><?= crm_h($activity['due_at'] ?: '-') ?></td>
                                    <td><?= crm_h($activity['assigned_to'] ?: '-') ?></td>
                                    <td>
                                        <form method="POST" action="crm.php" class="crm-inline">
                                            <input type="hidden" name="action" value="complete_activity">
                                            <input type="hidden" name="activity_id" value="<?= crm_h($activity['id']) ?>">
                                            <input name="outcome" placeholder="Outcome">
                                            <button type="submit">Done</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div class="crm-tab-panel" data-panel="leads">
        <section class="crm-grid">
            <div class="crm-panel crm-span-12 crm-entry-panel crm-lead-entry">
                <div class="crm-title"><i class="fa-solid fa-user-plus"></i> Capture Lead</div>
                <form class="crm-form crm-compact-form" method="POST" action="crm.php">
                    <input type="hidden" name="action" value="add_lead">
                    <div class="crm-field medium"><label>Contact Name</label><input name="contact_name" required placeholder="Main person"></div>
                    <div class="crm-field medium"><label>Company</label><input name="company_name" placeholder="Company or organization"></div>
                    <div class="crm-field"><label>Phone</label><input name="phone"></div>
                    <div class="crm-field medium"><label>Email</label><input type="email" name="email"></div>
                    <div class="crm-field"><label>Source</label><select name="source"><?php crm_select_options(['walk_in'=>'Walk-in','referral'=>'Referral','campaign'=>'Campaign','website'=>'Website','field_sales'=>'Field Sales','existing_client'=>'Existing Client'], 'walk_in'); ?></select></div>
                    <div class="crm-field"><label>Status</label><select name="status"><?php crm_select_options(['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal'], 'new'); ?></select></div>
                    <div class="crm-field"><label>Priority</label><select name="priority"><?php crm_select_options(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'], 'normal'); ?></select></div>
                    <div class="crm-field medium"><label>Owner</label><input name="assigned_to" value="<?= crm_h(session('user_name', 'Operator')) ?>"></div>
                    <div class="crm-field"><label>Value</label><input type="number" step="0.01" name="estimated_value" value="0.00"></div>
                    <div class="crm-field"><label>Close Date</label><input type="date" name="expected_close_date"></div>
                    <div class="crm-field medium"><label>Link Customer</label><select name="customer_id"><option value="">No customer yet</option><?php foreach ($customers as $customer): ?><option value="<?= crm_h($customer['id']) ?>"><?= crm_h($customer['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="crm-field wide"><label>Notes</label><textarea name="notes"></textarea></div>
                    <div class="crm-actions crm-field"><button class="crm-btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Lead</button></div>
                </form>
            </div>
            <div class="crm-panel crm-span-12 crm-register-panel crm-lead-register">
                <div class="crm-title"><i class="fa-solid fa-address-card"></i> Lead Register</div>
                <div class="crm-table-wrap">
                    <table class="crm-table">
                        <thead><tr><th>Lead</th><th>Contact</th><th>Source</th><th>Value</th><th>Status</th><th>Owner</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php if ($leads === []): ?>
                            <tr><td colspan="7" class="crm-muted">No CRM leads yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($leads as $lead): ?>
                                <?php $statusClass = in_array($lead['status'], ['converted', 'qualified'], true) ? 'ok' : (in_array($lead['status'], ['lost'], true) ? 'danger' : 'warn'); ?>
                                <tr>
                                    <td><div class="crm-name"><strong><?= crm_h($lead['company_name'] ?: $lead['contact_name']) ?></strong><span><?= crm_h($lead['customer_name'] ?: 'Prospect') ?></span></div></td>
                                    <td><?= crm_h($lead['phone'] ?: '-') ?><span class="crm-muted"><?= crm_h($lead['email'] ?: '-') ?></span></td>
                                    <td><?= crm_h(ucwords(str_replace('_', ' ', $lead['source'] ?: 'walk_in'))) ?></td>
                                    <td><?= crm_h(crm_money($lead['estimated_value'], $currency)) ?></td>
                                    <td><?= crm_badge($lead['status'] ?: 'new', $statusClass) ?></td>
                                    <td><?= crm_h($lead['assigned_to'] ?: '-') ?></td>
                                    <td>
                                        <form method="POST" action="crm.php" class="crm-inline">
                                            <input type="hidden" name="action" value="update_lead_status">
                                            <input type="hidden" name="lead_id" value="<?= crm_h($lead['id']) ?>">
                                            <select name="status"><?php crm_select_options(['new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified','proposal'=>'Proposal','lost'=>'Lost'], $lead['status']); ?></select>
                                            <button type="submit">Update</button>
                                        </form>
                                        <?php if (($lead['status'] ?? '') !== 'converted'): ?>
                                            <form method="POST" action="crm.php" class="crm-inline" style="margin-top:6px;">
                                                <input type="hidden" name="action" value="convert_lead">
                                                <input type="hidden" name="lead_id" value="<?= crm_h($lead['id']) ?>">
                                                <button class="primary" type="submit">Convert</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div class="crm-tab-panel" data-panel="pipeline">
        <section class="crm-grid">
            <div class="crm-panel crm-span-12 crm-entry-panel">
                <div class="crm-title"><i class="fa-solid fa-bullseye"></i> Add Opportunity</div>
                <form class="crm-form" method="POST" action="crm.php">
                    <input type="hidden" name="action" value="add_opportunity">
                    <div class="crm-field full"><label>Opportunity</label><input name="title" required placeholder="Deal, contract, or sale"></div>
                    <div class="crm-field full"><label>Lead</label><select name="lead_id"><option value="">No lead</option><?php foreach ($leads as $lead): ?><option value="<?= crm_h($lead['id']) ?>"><?= crm_h(($lead['company_name'] ?: $lead['contact_name']) . ' · ' . ($lead['status'] ?: 'new')) ?></option><?php endforeach; ?></select></div>
                    <div class="crm-field full"><label>Customer</label><select name="customer_id"><option value="">No customer</option><?php foreach ($customers as $customer): ?><option value="<?= crm_h($customer['id']) ?>"><?= crm_h($customer['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="crm-field"><label>Stage</label><select name="stage"><?php crm_select_options(['qualification'=>'Qualification','proposal'=>'Proposal','negotiation'=>'Negotiation'], 'qualification'); ?></select></div>
                    <div class="crm-field"><label>Value</label><input type="number" step="0.01" name="value" value="0.00"></div>
                    <div class="crm-field"><label>Close Date</label><input type="date" name="expected_close_date"></div>
                    <div class="crm-field full"><label>Owner</label><input name="owner" value="<?= crm_h(session('user_name', 'Operator')) ?>"></div>
                    <div class="crm-field full"><label>Notes</label><textarea name="notes"></textarea></div>
                    <div class="crm-actions crm-field full"><button class="crm-btn primary" type="submit"><i class="fa-solid fa-plus"></i> Add Opportunity</button></div>
                </form>
            </div>
            <div class="crm-panel crm-span-12 crm-register-panel">
                <div class="crm-title"><i class="fa-solid fa-chart-line"></i> Pipeline Register</div>
                <div class="crm-table-wrap">
                    <table class="crm-table">
                        <thead><tr><th>Opportunity</th><th>Account</th><th>Value</th><th>Probability</th><th>Close</th><th>Stage</th><th>Move</th></tr></thead>
                        <tbody>
                        <?php if ($opportunities === []): ?>
                            <tr><td colspan="7" class="crm-muted">No opportunities yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($opportunities as $opp): ?>
                                <tr>
                                    <td><div class="crm-name"><strong><?= crm_h($opp['title']) ?></strong><span><?= crm_h($opp['owner'] ?: '-') ?></span></div></td>
                                    <td><?= crm_h($opp['customer_name'] ?: $opp['lead_name'] ?: '-') ?></td>
                                    <td><?= crm_h(crm_money($opp['value'], $currency)) ?></td>
                                    <td><?= crm_h($opp['probability']) ?>%</td>
                                    <td><?= crm_h($opp['expected_close_date'] ?: '-') ?></td>
                                    <td><?= crm_badge($opp['stage'] ?: 'qualification', in_array($opp['stage'], ['won'], true) ? 'ok' : ($opp['stage'] === 'lost' ? 'danger' : 'info')) ?></td>
                                    <td>
                                        <form method="POST" action="crm.php" class="crm-inline">
                                            <input type="hidden" name="action" value="update_opportunity_stage">
                                            <input type="hidden" name="opportunity_id" value="<?= crm_h($opp['id']) ?>">
                                            <select name="stage"><?php crm_select_options(['qualification'=>'Qualification','proposal'=>'Proposal','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost'], $opp['stage']); ?></select>
                                            <button type="submit">Move</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div class="crm-tab-panel" data-panel="activities">
        <section class="crm-grid">
            <div class="crm-panel crm-span-12 crm-entry-panel">
                <div class="crm-title"><i class="fa-solid fa-calendar-check"></i> Schedule Activity</div>
                <form class="crm-form" method="POST" action="crm.php">
                    <input type="hidden" name="action" value="add_activity">
                    <div class="crm-field full"><label>Subject</label><input name="subject" required placeholder="Call, meeting, email, visit"></div>
                    <div class="crm-field"><label>Type</label><select name="activity_type"><?php crm_select_options(['call'=>'Call','meeting'=>'Meeting','email'=>'Email','visit'=>'Visit','follow_up'=>'Follow-up','demo'=>'Demo'], 'follow_up'); ?></select></div>
                    <div class="crm-field"><label>Due Date</label><input type="date" name="due_date" value="<?= crm_h(date('Y-m-d')) ?>"></div>
                    <div class="crm-field"><label>Due Time</label><input type="time" name="due_time" value="09:00"></div>
                    <div class="crm-field"><label>Priority</label><select name="priority"><?php crm_select_options(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'], 'normal'); ?></select></div>
                    <div class="crm-field wide"><label>Owner</label><input name="assigned_to" value="<?= crm_h(session('user_name', 'Operator')) ?>"></div>
                    <div class="crm-field full"><label>Lead</label><select name="lead_id"><option value="">No lead</option><?php foreach ($leads as $lead): ?><option value="<?= crm_h($lead['id']) ?>"><?= crm_h($lead['company_name'] ?: $lead['contact_name']) ?></option><?php endforeach; ?></select></div>
                    <div class="crm-field full"><label>Opportunity</label><select name="opportunity_id"><option value="">No opportunity</option><?php foreach ($opportunities as $opp): ?><option value="<?= crm_h($opp['id']) ?>"><?= crm_h($opp['title']) ?></option><?php endforeach; ?></select></div>
                    <div class="crm-field full"><label>Customer</label><select name="customer_id"><option value="">No customer</option><?php foreach ($customers as $customer): ?><option value="<?= crm_h($customer['id']) ?>"><?= crm_h($customer['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="crm-field full"><label>Notes</label><textarea name="notes"></textarea></div>
                    <div class="crm-actions crm-field full"><button class="crm-btn primary" type="submit"><i class="fa-solid fa-calendar-plus"></i> Schedule</button></div>
                </form>
            </div>
            <div class="crm-panel crm-span-12 crm-register-panel">
                <div class="crm-title"><i class="fa-solid fa-list-check"></i> Activity Register</div>
                <div class="crm-table-wrap">
                    <table class="crm-table">
                        <thead><tr><th>Activity</th><th>Linked To</th><th>Due</th><th>Priority</th><th>Status</th><th>Owner</th><th>Complete</th></tr></thead>
                        <tbody>
                        <?php if ($activities === []): ?>
                            <tr><td colspan="7" class="crm-muted">No activities scheduled.</td></tr>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                                <?php $isOverdue = $activity['status'] === 'open' && ! empty($activity['due_at']) && strtotime($activity['due_at']) < time(); ?>
                                <tr>
                                    <td><div class="crm-name"><strong><?= crm_h($activity['subject']) ?></strong><span><?= crm_h(ucwords(str_replace('_', ' ', $activity['activity_type']))) ?></span></div></td>
                                    <td><?= crm_h($activity['customer_name'] ?: $activity['lead_name'] ?: $activity['opportunity_title'] ?: '-') ?></td>
                                    <td><?= crm_h($activity['due_at'] ?: '-') ?></td>
                                    <td><?= crm_badge($activity['priority'] ?: 'normal', in_array($activity['priority'], ['high', 'urgent'], true) ? 'danger' : '') ?></td>
                                    <td><?= crm_badge($activity['status'] ?: 'open', $activity['status'] === 'completed' ? 'ok' : ($isOverdue ? 'danger' : 'warn')) ?></td>
                                    <td><?= crm_h($activity['assigned_to'] ?: '-') ?></td>
                                    <td>
                                        <?php if ($activity['status'] !== 'completed'): ?>
                                            <form method="POST" action="crm.php" class="crm-inline">
                                                <input type="hidden" name="action" value="complete_activity">
                                                <input type="hidden" name="activity_id" value="<?= crm_h($activity['id']) ?>">
                                                <input name="outcome" placeholder="Outcome">
                                                <button type="submit">Done</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="crm-muted"><?= crm_h($activity['outcome'] ?: 'Completed') ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div class="crm-tab-panel" data-panel="campaigns">
        <section class="crm-grid">
            <div class="crm-panel crm-span-12 crm-entry-panel">
                <div class="crm-title"><i class="fa-solid fa-bullhorn"></i> Add Campaign</div>
                <form class="crm-form" method="POST" action="crm.php">
                    <input type="hidden" name="action" value="add_campaign">
                    <div class="crm-field full"><label>Campaign Name</label><input name="name" required placeholder="July field outreach"></div>
                    <div class="crm-field"><label>Channel</label><select name="channel"><?php crm_select_options(['field_sales'=>'Field Sales','referral'=>'Referral','website'=>'Website','email'=>'Email','social'=>'Social','event'=>'Event'], 'field_sales'); ?></select></div>
                    <div class="crm-field"><label>Budget</label><input type="number" step="0.01" name="budget" value="0.00"></div>
                    <div class="crm-field"><label>Status</label><select name="status"><?php crm_select_options(['active'=>'Active','planned'=>'Planned','paused'=>'Paused','completed'=>'Completed'], 'active'); ?></select></div>
                    <div class="crm-field"><label>Start</label><input type="date" name="start_date"></div>
                    <div class="crm-field"><label>End</label><input type="date" name="end_date"></div>
                    <div class="crm-field full"><label>Notes</label><textarea name="notes"></textarea></div>
                    <div class="crm-actions crm-field full"><button class="crm-btn primary" type="submit"><i class="fa-solid fa-plus"></i> Add Campaign</button></div>
                </form>
            </div>
            <div class="crm-panel crm-span-12 crm-register-panel">
                <div class="crm-title"><i class="fa-solid fa-chart-simple"></i> Campaign Register</div>
                <div class="crm-table-wrap">
                    <table class="crm-table">
                        <thead><tr><th>Campaign</th><th>Channel</th><th>Budget</th><th>Dates</th><th>Status</th><th>Notes</th></tr></thead>
                        <tbody>
                        <?php if ($campaigns === []): ?>
                            <tr><td colspan="6" class="crm-muted">No campaigns yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($campaigns as $campaign): ?>
                                <tr>
                                    <td><strong><?= crm_h($campaign['name']) ?></strong></td>
                                    <td><?= crm_h(ucwords(str_replace('_', ' ', $campaign['channel']))) ?></td>
                                    <td><?= crm_h(crm_money($campaign['budget'], $currency)) ?></td>
                                    <td><?= crm_h(($campaign['start_date'] ?: '-') . ' to ' . ($campaign['end_date'] ?: '-')) ?></td>
                                    <td><?= crm_badge($campaign['status'] ?: 'active', $campaign['status'] === 'completed' ? 'ok' : 'info') ?></td>
                                    <td><?= crm_h($campaign['notes'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    document.querySelectorAll('[data-crm-tabs] .crm-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            document.querySelectorAll('[data-crm-tabs] .crm-tab').forEach((item) => item.classList.toggle('active', item === tab));
            document.querySelectorAll('.crm-tab-panel').forEach((panel) => panel.classList.toggle('active', panel.dataset.panel === target));
        });
    });
</script>

<?php onyx_page_end(); ?>
