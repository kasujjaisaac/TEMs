<?php
require_once __DIR__ . '/includes/erp_layout.php';

$tenant_id = onyx_tenant_id();
$pdo = onyx_db();

function ensure_tenant_settings_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS tenant_settings ('
        . ' tenant_id BIGINT(20) NOT NULL,'
        . ' setting_key VARCHAR(100) NOT NULL,'
        . ' setting_value TEXT DEFAULT NULL,'
        . ' PRIMARY KEY (tenant_id, setting_key)'
        . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
    );
}

function load_tenant_settings(PDO $pdo, int $tenant_id): array
{
    ensure_tenant_settings_table($pdo);
    $rows = onyx_rows('SELECT setting_key, setting_value FROM tenant_settings WHERE tenant_id = ?', [$tenant_id]);
    return array_column($rows, 'setting_value', 'setting_key');
}

function save_tenant_settings(PDO $pdo, int $tenant_id, array $settings): void
{
    ensure_tenant_settings_table($pdo);
    $stmt = $pdo->prepare(
        'INSERT INTO tenant_settings (tenant_id, setting_key, setting_value) VALUES (?, ?, ?) '
        . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );

    foreach ($settings as $key => $value) {
        $stmt->execute([$tenant_id, $key, $value]);
    }
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$tenant = onyx_row('SELECT * FROM tenants WHERE id = ? LIMIT 1', [$tenant_id]);
if ($tenant === false) {
    $tenant = [
        'company_name' => '',
        'currency' => 'UGX',
        'tin_number' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'fiscal_year_start' => '',
    ];
}

$extra_settings = load_tenant_settings($pdo, $tenant_id);

$company_name = $tenant['company_name'] ?? '';
$company_logo = $extra_settings['company_logo'] ?? '';
$business_registration_number = $extra_settings['business_registration_number'] ?? '';
$tin = $tenant['tin_number'] ?? '';
$vat_registration_number = $extra_settings['vat_registration_number'] ?? '';
$company_email = $tenant['email'] ?? '';
$company_phone = $tenant['phone'] ?? '';
$company_website = $extra_settings['company_website'] ?? '';
$company_address = $tenant['address'] ?? '';
$company_country = $extra_settings['company_country'] ?? '';
$company_currency = $tenant['currency'] ?? 'UGX';
$financial_year = $extra_settings['financial_year'] ?? '';
$fiscal_year_start = $tenant['fiscal_year_start'] ?? '';
$company_time_zone = $extra_settings['company_time_zone'] ?? '';
$company_tagline = $extra_settings['company_tagline'] ?? '';
$company_profile = $extra_settings['company_profile'] ?? '';
$company_mission = $extra_settings['company_mission'] ?? '';
$company_vision = $extra_settings['company_vision'] ?? '';
$date_format = $extra_settings['date_format'] ?? 'Y-m-d';
$time_format = $extra_settings['time_format'] ?? 'H:i';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $company_logo = trim($_POST['company_logo'] ?? '');
    $business_registration_number = trim($_POST['business_registration_number'] ?? '');
    $tin = trim($_POST['tin'] ?? '');
    $vat_registration_number = trim($_POST['vat_registration_number'] ?? '');
    $company_email = trim($_POST['company_email'] ?? '');
    $company_phone = trim($_POST['company_phone'] ?? '');
    $company_website = trim($_POST['company_website'] ?? '');
    $company_address = trim($_POST['company_address'] ?? '');
    $company_country = trim($_POST['company_country'] ?? '');
    $company_currency = trim($_POST['company_currency'] ?? 'UGX');
    $financial_year = trim($_POST['financial_year'] ?? '');
    $fiscal_year_start = trim($_POST['fiscal_year_start'] ?? '');
    $company_time_zone = trim($_POST['company_time_zone'] ?? '');
    $company_tagline = trim($_POST['company_tagline'] ?? '');
    $company_profile = trim($_POST['company_profile'] ?? '');
    $company_mission = trim($_POST['company_mission'] ?? '');
    $company_vision = trim($_POST['company_vision'] ?? '');
    $date_format = trim($_POST['date_format'] ?? 'Y-m-d');
    $time_format = trim($_POST['time_format'] ?? 'H:i');

    if (!empty($_FILES['company_logo_file']['name']) && $_FILES['company_logo_file']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/png', 'image/jpeg', 'image/webp'];
        $tmpFile = $_FILES['company_logo_file']['tmp_name'];
        $mimeType = mime_content_type($tmpFile) ?: '';
        if (!in_array($mimeType, $allowed, true)) {
            $errors[] = 'Uploaded logo must be a PNG, JPEG, or WEBP image.';
        } else {
            $uploadsDir = __DIR__ . '/assets/uploads/company_logos';
            if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
                $errors[] = 'Unable to create logo upload directory.';
            } else {
                $ext = pathinfo($_FILES['company_logo_file']['name'], PATHINFO_EXTENSION);
                $ext = strtolower($ext ?: 'png');
                $filename = sprintf('%s_%s.%s', $tenant_id, time(), preg_replace('/[^a-z0-9]/', '', $ext));
                $destination = $uploadsDir . '/' . $filename;
                if (!move_uploaded_file($tmpFile, $destination)) {
                    $errors[] = 'Unable to save the uploaded logo file.';
                } else {
                    $company_logo = 'assets/uploads/company_logos/' . $filename;
                }
            }
        }
    }

    if ($company_name === '') {
        $errors[] = 'Company name is required.';
    }
    if ($company_email !== '' && !filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($company_website !== '' && !filter_var($company_website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid website URL.';
    }
    if ($fiscal_year_start !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fiscal_year_start)) {
        $errors[] = 'Fiscal year start must be a valid date in YYYY-MM-DD format.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE tenants SET company_name = ?, currency = ?, tin_number = ?, address = ?, phone = ?, email = ?, fiscal_year_start = ? WHERE id = ?'
        );
        $stmt->execute([
            $company_name,
            $company_currency,
            $tin,
            $company_address,
            $company_phone,
            $company_email,
            $fiscal_year_start ?: null,
            $tenant_id,
        ]);

        save_tenant_settings($pdo, $tenant_id, [
            'company_logo' => $company_logo,
            'business_registration_number' => $business_registration_number,
            'vat_registration_number' => $vat_registration_number,
            'company_website' => $company_website,
            'company_country' => $company_country,
            'financial_year' => $financial_year,
            'company_time_zone' => $company_time_zone,
            'company_tagline' => $company_tagline,
            'company_profile' => $company_profile,
            'company_mission' => $company_mission,
            'company_vision' => $company_vision,
            'date_format' => $date_format,
            'time_format' => $time_format,
        ]);

        $_SESSION['company_name'] = $company_name;
        $_SESSION['currency'] = $company_currency;
        $_SESSION['email'] = $company_email;
        $_SESSION['company_logo'] = $company_logo;
        $_SESSION['company_tagline'] = $company_tagline;
        $_SESSION['company_profile'] = $company_profile;

        $success = 'Company settings saved successfully.';
    }
}

$context = onyx_page_start('Company Settings', 'Update company details, legal identifiers, and fiscal preferences.');
?>

<style>
    .company-settings-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(260px, 0.85fr);
        gap: 24px;
        margin-top: 16px;
    }

    .panel,
    .summary-panel {
        border-radius: 24px;
        background: rgba(15, 23, 42, 0.95);
        border: 1px solid rgba(148, 163, 184, 0.12);
        padding: 28px;
        box-shadow: 0 24px 56px rgba(0, 0, 0, 0.18);
    }

    .panel h1,
    .summary-panel h2 {
        margin: 0 0 10px;
        color: #f8fafc;
    }

    .panel p,
    .summary-panel p {
        margin: 0 0 24px;
        color: #94a3b8;
        line-height: 1.8;
    }

    .message {
        padding: 18px 20px;
        border-radius: 18px;
        margin-bottom: 24px;
        line-height: 1.6;
    }

    .message.success {
        background: rgba(34, 197, 94, 0.14);
        border: 1px solid rgba(34, 197, 94, 0.28);
        color: #dcfce7;
    }

    .message.error {
        background: rgba(248, 113, 113, 0.14);
        border: 1px solid rgba(248, 113, 113, 0.28);
        color: #fecaca;
    }

    .section {
        margin-bottom: 32px;
    }

    .section h2,
    .section h3 {
        margin: 0 0 16px;
        color: #ffffff;
    }

    .section h3 {
        font-size: 1.1rem;
    }

    .section p {
        margin: 0 0 20px;
        color: #94a3b8;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(220px, 1fr));
        gap: 18px;
    }

    .form-group {
        display: grid;
        gap: 8px;
    }

    .form-group.full {
        grid-column: 1 / -1;
    }

    .form-group label {
        color: #cbd5e1;
        font-size: 0.94rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.85);
        color: #f8fafc;
        padding: 14px 16px;
        font-size: 0.95rem;
    }
    .form-group textarea {
        min-height: 130px;
        resize: vertical;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: 0;
        border-color: rgba(59, 130, 246, 0.75);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 12px;
    }

    .actions button {
        border: none;
        border-radius: 999px;
        background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
        color: #ffffff;
        cursor: pointer;
        font-weight: 700;
        padding: 14px 26px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .actions button:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 40px rgba(249, 115, 22, 0.22);
    }

    .summary-panel img {
        width: 100%;
        border-radius: 18px;
        object-fit: contain;
        margin-bottom: 22px;
        max-height: 190px;
        display: block;
    }

    .summary-list {
        display: grid;
        gap: 16px;
    }

    .summary-item {
        display: grid;
        gap: 6px;
    }

    .summary-item label {
        color: #94a3b8;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
    }

    .summary-item span {
        color: #f8fafc;
        font-size: 0.96rem;
        line-height: 1.7;
        white-space: pre-wrap;
    }

    @media (max-width: 960px) {
        .company-settings-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="company-settings-layout">
    <section class="panel">
        <div class="section">
            <h1>Company Settings</h1>
            <p>Change your tenant’s company profile, contact details, fiscal year, and legal identifiers from one clean dashboard.</p>
        </div>

        <?php if ($success): ?>
            <div class="message success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <ul style="margin:0; padding-left:20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" enctype="multipart/form-data">
            <div class="section">
                <h3>Business Information</h3>
                <p>Update the company name, branding, contact information, and location used across documents and reports.</p>
                <div class="form-grid">
                    <div class="form-group full">
                        <label for="company_name">Company Name</label>
                        <input id="company_name" name="company_name" type="text" value="<?= h($company_name) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="company_logo">Logo URL or relative path</label>
                        <input id="company_logo" name="company_logo" type="text" value="<?= h($company_logo) ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_logo_file">Upload Logo</label>
                        <input id="company_logo_file" name="company_logo_file" type="file" accept="image/png,image/jpeg,image/webp">
                    </div>
                    <div class="form-group full">
                        <label for="company_tagline">Tagline</label>
                        <input id="company_tagline" name="company_tagline" type="text" value="<?= h($company_tagline) ?>">
                    </div>
                    <div class="form-group full">
                        <label for="company_profile">Company Profile</label>
                        <textarea id="company_profile" name="company_profile"><?= h($company_profile) ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label for="company_mission">Mission Statement</label>
                        <textarea id="company_mission" name="company_mission"><?= h($company_mission) ?></textarea>
                    </div>
                    <div class="form-group full">
                        <label for="company_vision">Vision Statement</label>
                        <textarea id="company_vision" name="company_vision"><?= h($company_vision) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="company_website">Website</label>
                        <input id="company_website" name="company_website" type="url" value="<?= h($company_website) ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_email">Email Address</label>
                        <input id="company_email" name="company_email" type="email" value="<?= h($company_email) ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_phone">Phone Number</label>
                        <input id="company_phone" name="company_phone" type="tel" value="<?= h($company_phone) ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_address">Address</label>
                        <input id="company_address" name="company_address" type="text" value="<?= h($company_address) ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_country">Country</label>
                        <input id="company_country" name="company_country" type="text" value="<?= h($company_country) ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_currency">Currency</label>
                        <input id="company_currency" name="company_currency" type="text" value="<?= h($company_currency) ?>">
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Legal & Fiscal Settings</h3>
                <p>Store business registration numbers, tax identifiers, fiscal period details, and formatting preferences.</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="business_registration_number">Business Registration Number</label>
                        <input id="business_registration_number" name="business_registration_number" type="text" value="<?= h($business_registration_number) ?>">
                    </div>
                    <div class="form-group">
                        <label for="tin">Tax ID / TIN</label>
                        <input id="tin" name="tin" type="text" value="<?= h($tin) ?>">
                    </div>
                    <div class="form-group">
                        <label for="vat_registration_number">VAT Registration Number</label>
                        <input id="vat_registration_number" name="vat_registration_number" type="text" value="<?= h($vat_registration_number) ?>">
                    </div>
                    <div class="form-group">
                        <label for="financial_year">Financial Year</label>
                        <input id="financial_year" name="financial_year" type="text" value="<?= h($financial_year) ?>">
                    </div>
                    <div class="form-group">
                        <label for="fiscal_year_start">Fiscal Year Start</label>
                        <input id="fiscal_year_start" name="fiscal_year_start" type="date" value="<?= h($fiscal_year_start) ?>">
                    </div>
                    <div class="form-group">
                        <label for="company_time_zone">Time Zone</label>
                        <input id="company_time_zone" name="company_time_zone" type="text" value="<?= h($company_time_zone) ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_format">Date Format</label>
                        <select id="date_format" name="date_format">
                            <option value="Y-m-d" <?= $date_format === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                            <option value="d/m/Y" <?= $date_format === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                            <option value="m/d/Y" <?= $date_format === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="time_format">Time Format</label>
                        <select id="time_format" name="time_format">
                            <option value="H:i" <?= $time_format === 'H:i' ? 'selected' : '' ?>>24-hour</option>
                            <option value="h:i A" <?= $time_format === 'h:i A' ? 'selected' : '' ?>>12-hour</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="actions">
                <button type="submit">Save Company Settings</button>
            </div>
        </form>
    </section>

    <aside class="summary-panel">
        <h2>Company Snapshot</h2>
        <p>This summary shows the settings currently stored for this tenant.</p>

        <?php if ($company_logo): ?>
            <img src="<?= h($company_logo) ?>" alt="Company Logo">
        <?php endif; ?>

        <div class="summary-list">
            <div class="summary-item">
                <label>Company Name</label>
                <span><?= h($company_name ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Email</label>
                <span><?= h($company_email ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Phone</label>
                <span><?= h($company_phone ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Website</label>
                <span><?= h($company_website ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Tagline</label>
                <span><?= h($company_tagline ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Mission</label>
                <span><?= h($company_mission ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Vision</label>
                <span><?= h($company_vision ?: 'Not set') ?></span>
            </div>
            <div class="summary-item full">
                <label>Profile</label>
                <span><?= h($company_profile ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Address</label>
                <span><?= h($company_address ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Country</label>
                <span><?= h($company_country ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Currency</label>
                <span><?= h($company_currency) ?></span>
            </div>
            <div class="summary-item">
                <label>Fiscal Year Start</label>
                <span><?= h($fiscal_year_start ?: 'Not set') ?></span>
            </div>
            <div class="summary-item">
                <label>Date Format</label>
                <span><?= h($date_format) ?></span>
            </div>
            <div class="summary-item">
                <label>Time Format</label>
                <span><?= h($time_format) ?></span>
            </div>
        </div>
    </aside>
</div>

<?php onyx_page_end(); ?>
