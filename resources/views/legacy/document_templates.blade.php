<?php
function doc_tpl_h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$tenant_id = onyx_tenant_id();
$currency = session('currency', config('app.currency', 'UGX'));
$settingsRows = onyx_rows('SELECT setting_key, setting_value FROM tenant_settings WHERE tenant_id = :tenant_id', ['tenant_id' => $tenant_id]);
$settings = [];
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$companyName = session('company_name', config('app.name', 'Texaro Technologies Limited'));
$companyLogo = trim((string) ($settings['company_logo'] ?? '')) ?: asset('assets/texaro-logo.png');
$companyAddress = trim((string) ($settings['physical_address'] ?? 'Kampala, Uganda'));
$companyPhone = trim((string) ($settings['phone_number'] ?? '+256 700 000 000'));
$companyEmail = trim((string) ($settings['email_address'] ?? 'accounts@company.test'));
$companyWebsite = trim((string) ($settings['company_website'] ?? ''));
$colorOr = static fn (string $value, string $fallback): string => preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
$invoiceAccent = $colorOr((string) ($settings['invoice_accent_color'] ?? ''), '#51439a');
$quotationAccent = $colorOr((string) ($settings['quotation_accent_color'] ?? ''), '#55734f');
$receiptAccent = $colorOr((string) ($settings['receipt_accent_color'] ?? ''), '#111111');
$logoSize = ['small' => 46, 'medium' => 58, 'large' => 72][$settings['document_logo_size'] ?? 'medium'] ?? 58;
$showSignature = ($settings['document_show_signature'] ?? 'yes') !== 'no';
$showDiscount = ($settings['document_show_discount'] ?? 'yes') !== 'no';
$paymentAccountName = trim((string) ($settings['document_payment_account_name'] ?? $companyName));
$paymentAccountNumber = trim((string) ($settings['document_payment_account_number'] ?? ''));
$paymentBankName = trim((string) ($settings['document_payment_bank_name'] ?? ($settings['bank_account'] ?? 'Main Bank')));
$paymentMobileMoney = trim((string) ($settings['document_payment_mobile_money'] ?? $companyPhone));
$paymentMethodsNote = trim((string) ($settings['document_payment_methods_note'] ?? 'Cash, mobile money, bank transfer, cheque.'));

$templates = [
    'invoice' => [
        'key' => 'invoice',
        'icon' => 'fa-file-invoice-dollar',
        'title' => 'Invoice',
        'number' => ($settings['invoice_prefix'] ?? 'INV') . '-' . ($settings['next_invoice_number'] ?? '0001'),
        'accent' => $invoiceAccent,
        'meta' => ['Invoice Date' => date('Y-m-d'), 'Due Date' => date('Y-m-d', strtotime('+7 days')), 'Terms' => 'Net 7'],
        'footer' => $settings['invoice_footer'] ?? 'Thank you for your business.',
        'description' => 'Customer billing document with tax, totals, payment terms, and branded company details.',
        'used_by' => 'Sales invoices',
    ],
    'receipt' => [
        'key' => 'receipt',
        'icon' => 'fa-receipt',
        'title' => 'Payment Receipt',
        'number' => ($settings['receipt_prefix'] ?? 'RCT') . '-' . ($settings['next_receipt_number'] ?? '0001'),
        'accent' => $receiptAccent,
        'meta' => ['Receipt Date' => date('Y-m-d'), 'Method' => 'Cash / Mobile Money', 'Reference' => 'PAY-0001'],
        'footer' => $settings['receipt_footer'] ?? 'Goods sold are subject to company policy.',
        'description' => 'Payment confirmation for POS and invoice receipts with method and reference details.',
        'used_by' => 'POS and payments',
    ],
    'quotation' => [
        'key' => 'quotation',
        'icon' => 'fa-file-signature',
        'title' => 'Quotation',
        'number' => ($settings['quotation_prefix'] ?? 'QT') . '-' . ($settings['next_quotation_number'] ?? '0001'),
        'accent' => $quotationAccent,
        'meta' => ['Quotation Date' => date('Y-m-d'), 'Valid Until' => date('Y-m-d', strtotime('+7 days')), 'Prepared By' => session('user_name', 'Operator')],
        'footer' => $settings['quotation_terms'] ?? 'This quotation is valid for 7 days.',
        'description' => 'Price offer document with validity dates, customer details, and approval-ready totals.',
        'used_by' => 'Sales quotations',
    ],
];

$sampleLines = [
    ['Premium product / service package', 2, 250000],
    ['Installation and onboarding', 1, 150000],
    ['Support and configuration', 1, 85000],
];
$subtotal = array_sum(array_map(static fn (array $line): float => $line[1] * $line[2], $sampleLines));
$tax = $subtotal * 0.18;
$total = $subtotal + $tax;
$previewKey = $_GET['preview'] ?? '';

if ($previewKey !== '' && isset($templates[$previewKey])) {
    $template = $templates[$previewKey];
    $taxLabel = trim((string) ($settings['invoice_tax_label'] ?? 'VAT'));
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= doc_tpl_h($template['title']) ?> Preview</title>
    <style>
        :root{--invoice-accent:<?= doc_tpl_h($invoiceAccent) ?>;--quotation-accent:<?= doc_tpl_h($quotationAccent) ?>;--receipt-accent:<?= doc_tpl_h($receiptAccent) ?>;--doc-logo-size:<?= (int) $logoSize ?>px}*{box-sizing:border-box}body{background:#d9d9d9;color:#111;font-family:Arial,Helvetica,sans-serif;margin:0;padding:28px}.preview-toolbar{align-items:center;display:flex;gap:10px;justify-content:flex-end;margin:0 auto 14px;max-width:900px}.preview-btn{background:#111;border:1px solid #111;color:#fff;cursor:pointer;display:inline-flex;font-size:11px;font-weight:800;min-height:36px;padding:0 12px;text-decoration:none;text-transform:uppercase}.preview-btn.ghost{background:#fff;color:#111}.sheet{background:#fff;box-shadow:0 22px 54px rgba(0,0,0,.22);margin:0 auto;max-width:820px;min-height:1050px;padding:54px 58px}.right{text-align:right}.muted{color:#555}.brand-logo,.quote-logo{height:var(--doc-logo-size);object-fit:contain;width:var(--doc-logo-size)}.invoice-bg{background:#c9c2ee}.invoice{color:#151326}.invoice-head{display:grid;grid-template-columns:1fr 1fr;gap:34px}.invoice-brand strong{display:block;font-size:25px;line-height:1.05;margin-top:10px}.invoice-brand span,.invoice-to span{display:block;font-size:12px;line-height:1.5}.invoice-title h1{color:var(--invoice-accent);font-size:34px;letter-spacing:2px;margin:6px 0 4px;text-transform:uppercase}.invoice-title strong{font-size:14px}.invoice-address{margin-top:28px}.invoice-to{margin-top:116px}.invoice-table{border-collapse:collapse;margin-top:28px;width:100%}.invoice-table th{background:var(--invoice-accent);color:#fff;font-size:12px;padding:14px;text-align:left}.invoice-table td{border-bottom:1px solid #222;font-size:12px;padding:16px 14px;vertical-align:top}.invoice-item strong{display:block;font-size:13px;margin-bottom:4px}.invoice-item span{font-size:9px;line-height:1.35}.invoice-summary{display:grid;grid-template-columns:1fr 250px;gap:30px;margin-top:24px}.invoice-note{align-self:end;font-size:10px;line-height:1.45}.invoice-note strong{display:block;margin-bottom:6px}.invoice-totals div{display:flex;font-size:12px;font-weight:700;justify-content:space-between;padding:8px 0}.invoice-due{align-items:center;background:var(--invoice-accent);color:#fff;display:flex;font-size:16px;font-weight:800;justify-content:space-between;margin-top:8px;padding:18px}.invoice-thanks{color:var(--invoice-accent);font-size:15px;font-weight:800;margin-top:44px}.invoice-footer{border-top:2px solid var(--invoice-accent);display:grid;gap:24px;grid-template-columns:1fr 1fr 1fr;margin-top:34px;padding-top:22px}.invoice-footer h4{color:var(--invoice-accent);font-size:12px;margin:0 0 9px}.invoice-footer p{font-size:9px;line-height:1.45;margin:0}.quote-bg{background:linear-gradient(#cfe6c9 0 31%,#dedede 31% 100%)}.quote{max-width:700px;min-height:930px;padding:54px}.quote-head{display:grid;grid-template-columns:1fr 1fr;gap:28px}.quote h1{font-size:36px;letter-spacing:1px;margin:8px 0 0;text-align:right;text-transform:uppercase}.quote-brand strong,.quote-brand span{display:block;font-size:12px;line-height:1.55}.quote-brand strong{margin-top:8px}.quote-no{font-size:12px;line-height:1.7;margin-top:52px}.quote-info{display:grid;gap:28px;grid-template-columns:1fr 1.5fr;margin-top:24px}.quote-box-title,.quote-table th,.quote-pay-title,.quote-amount-title{background:var(--quotation-accent);color:#fff;font-size:12px;font-weight:800;padding:5px 8px}.quote-box-row{background:#f8eedf;border-bottom:2px solid #fff;font-size:12px;min-height:28px;padding:7px 8px}.quote-section{font-size:13px;font-weight:800;margin:22px 0 10px}.quote-table{border-collapse:collapse;width:100%}.quote-table th{border-right:2px solid rgba(255,255,255,.25);text-align:left}.quote-table td{background:#f8eedf;border:2px solid #fff;font-size:12px;padding:7px 8px}.quote-bottom{display:grid;gap:28px;grid-template-columns:1fr 230px;margin-top:20px}.quote-pay p{font-size:12px;margin:8px 0 38px}.quote-lines{display:grid;gap:14px;font-size:12px}.quote-line{border-bottom:1px solid #e5e5e5;display:inline-block;width:150px}.quote-amount table{border-collapse:collapse;width:100%}.quote-amount td{background:#f8eedf;border:2px solid #fff;font-size:12px;padding:7px 8px}.quote-mark{color:#999;font-size:9px;margin-top:20px;text-align:right}.receipt{box-shadow:none;max-width:720px;min-height:900px;padding:34px}.receipt-head{display:grid;grid-template-columns:1fr 1fr}.receipt h1{font-size:44px;margin:0 0 28px}.receipt-company{font-size:12px;line-height:1.35;text-align:right}.receipt-meta{display:grid;gap:60px;grid-template-columns:1fr 1fr;font-size:12px;font-weight:800}.receipt-line{border-bottom:1px solid #111;display:block;height:12px;margin-top:2px}.receipt-bar{background:var(--receipt-accent);color:#fff;font-size:12px;font-weight:800;letter-spacing:1px;margin-top:8px;padding:9px;text-transform:uppercase}.receipt-fields{font-size:12px}.receipt-field{align-items:end;display:grid;gap:8px;grid-template-columns:auto 1fr;margin:9px 0}.receipt-field.two{grid-template-columns:auto 1fr auto 1fr}.receipt-table{border-collapse:collapse;margin-top:12px;width:100%}.receipt-table th,.receipt-table td{border:1px solid #555;font-size:12px;height:30px;padding:6px;text-align:left}.receipt-table th{text-align:center}.receipt-bottom{display:grid;grid-template-columns:1fr 230px;gap:40px;margin-top:22px}.receipt-check{font-size:12px;line-height:1.9}.box{border:1px solid #777;display:inline-block;height:16px;margin-right:8px;vertical-align:middle;width:16px}.receipt-total-row{align-items:end;display:grid;font-size:12px;grid-template-columns:1fr 120px;margin-bottom:10px}.receipt-total-row span:last-child{border-bottom:1px solid #111;height:14px}.receipt-thanks{border-top:1px solid #111;font-size:15px;font-weight:800;margin-top:38px;padding-top:12px;text-align:center}@media(max-width:720px){body{padding:10px}.sheet{min-height:auto;padding:24px}.invoice-head,.invoice-summary,.invoice-footer,.quote-head,.quote-info,.quote-bottom,.receipt-head,.receipt-bottom{grid-template-columns:1fr}.invoice-to{margin-top:20px}.quote h1{text-align:left}.receipt-company{text-align:left}}@media print{body{background:#fff!important;padding:0}.preview-toolbar{display:none}.sheet{box-shadow:none;max-width:none;min-height:auto}}
    </style>
</head>
<body class="<?= doc_tpl_h($previewKey) ?>-bg">
    <div class="preview-toolbar">
        <a class="preview-btn ghost" href="<?= doc_tpl_h(onyx_legacy_url('document_templates.php')) ?>">Back</a>
        <button class="preview-btn" type="button" onclick="window.print()">Print Preview</button>
    </div>

    <?php if ($previewKey === 'invoice'): ?>
        <main class="sheet invoice">
            <section class="invoice-head">
                <div class="invoice-brand">
                    <img class="brand-logo" src="<?= doc_tpl_h($companyLogo) ?>" alt="Company logo">
                    <strong><?= doc_tpl_h($companyName) ?></strong>
                    <div class="invoice-address">
                        <span><b>Office Address</b></span>
                        <span><?= doc_tpl_h($companyAddress) ?></span>
                        <span><?= doc_tpl_h($companyPhone) ?></span>
                    </div>
                </div>
                <div>
                    <div class="invoice-title right">
                        <h1>Invoice</h1>
                        <strong><?= doc_tpl_h(date('F d, Y')) ?></strong>
                    </div>
                    <div class="invoice-to">
                        <span><b>To:</b></span>
                        <span>Sample Customer Ltd</span>
                        <span>Kampala Road, Uganda</span>
                        <span><?= doc_tpl_h($template['number']) ?></span>
                    </div>
                </div>
            </section>
            <table class="invoice-table">
                <thead><tr><th>Items Description</th><th class="right">Unit Price</th><th class="right">Qnt</th><th class="right">Total</th></tr></thead>
                <tbody>
                    <?php foreach ($sampleLines as $line): ?>
                        <tr><td class="invoice-item"><strong><?= doc_tpl_h($line[0]) ?></strong><span>Business document line item generated from company transactions.</span></td><td class="right"><?= doc_tpl_h($currency . ' ' . number_format((float) $line[2], 2)) ?></td><td class="right"><?= doc_tpl_h($line[1]) ?></td><td class="right"><?= doc_tpl_h($currency . ' ' . number_format((float) ($line[1] * $line[2]), 2)) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <section class="invoice-summary">
                <div class="invoice-note"><strong>Note:</strong><?= doc_tpl_h($template['footer']) ?></div>
                <div class="invoice-totals">
                    <div><span>SUBTOTAL:</span><span><?= doc_tpl_h($currency . ' ' . number_format($subtotal, 2)) ?></span></div>
                    <div><span>Tax <?= doc_tpl_h($taxLabel) ?> 18%:</span><span><?= doc_tpl_h($currency . ' ' . number_format($tax, 2)) ?></span></div>
                    <?php if ($showDiscount): ?><div><span>DISCOUNT 0%:</span><span><?= doc_tpl_h($currency . ' 0.00') ?></span></div><?php endif; ?>
                    <div class="invoice-due"><span>TOTAL DUE:</span><span><?= doc_tpl_h($currency . ' ' . number_format($total, 2)) ?></span></div>
                </div>
            </section>
            <div class="invoice-thanks">Thank you for your Business</div>
            <section class="invoice-footer">
                <div><h4>Questions?</h4><p>Email us: <?= doc_tpl_h($companyEmail) ?><br>Call us: <?= doc_tpl_h($companyPhone) ?></p></div>
                <div><h4>Payment Info:</h4><p>Account: <?= doc_tpl_h($paymentAccountNumber ?: 'Company collections') ?><br>A/C Name: <?= doc_tpl_h($paymentAccountName) ?><br>Bank Detail: <?= doc_tpl_h($paymentBankName) ?><br>Mobile Money: <?= doc_tpl_h($paymentMobileMoney) ?></p></div>
                <div><h4>Terms & Conditions/Note:</h4><p><?= doc_tpl_h($template['footer']) ?></p></div>
            </section>
        </main>
    <?php elseif ($previewKey === 'quotation'): ?>
        <main class="sheet quote">
            <section class="quote-head">
                <div class="quote-brand">
                    <img class="quote-logo" src="<?= doc_tpl_h($companyLogo) ?>" alt="Company logo">
                    <strong><?= doc_tpl_h($companyName) ?></strong>
                    <span><?= doc_tpl_h($companyAddress) ?></span>
                    <span><?= doc_tpl_h($companyPhone) ?></span>
                    <span><?= doc_tpl_h($companyEmail) ?> <?= $companyWebsite ? '| ' . doc_tpl_h($companyWebsite) : '' ?></span>
                </div>
                <div>
                    <h1>Quotation</h1>
                    <div class="quote-no"><b>Quotation No.:</b> <?= doc_tpl_h($template['number']) ?><br><b>Date:</b> <?= doc_tpl_h(date('d-m-Y')) ?></div>
                </div>
            </section>
            <section class="quote-info">
                <div><div class="quote-box-title">Client Information:</div><div class="quote-box-row">Sample Customer Ltd</div><div class="quote-box-row">Kampala Road, Uganda</div><div class="quote-box-row">+256 700 111 222</div><div class="quote-box-row">accounts@customer.test</div></div>
                <div><div class="quote-box-title">Client Information:</div><div class="quote-box-row">Quotation date: <?= doc_tpl_h(date('d-m-Y')) ?></div><div class="quote-box-row">Quotation deadline: <?= doc_tpl_h(date('d-m-Y', strtotime('+7 days'))) ?></div><div class="quote-box-row">Quotation for: Sample Customer Ltd</div></div>
            </section>
            <div class="quote-section">Construction Materials:</div>
            <table class="quote-table">
                <thead><tr><th>Materials Description</th><th class="right">Quantity</th><th class="right">Unit Price</th><th class="right">Total Cost</th></tr></thead>
                <tbody>
                    <?php foreach ($sampleLines as $line): ?>
                        <tr><td><b><?= doc_tpl_h($line[0]) ?></b></td><td class="right"><?= doc_tpl_h($line[1]) ?></td><td class="right"><?= doc_tpl_h($currency . ' ' . number_format((float) $line[2], 2)) ?></td><td class="right"><?= doc_tpl_h($currency . ' ' . number_format((float) ($line[1] * $line[2]), 2)) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <section class="quote-bottom">
                <div class="quote-pay"><div class="quote-pay-title">Payment Method</div><p><?= doc_tpl_h($paymentMethodsNote) ?></p><?php if ($showSignature): ?><div class="quote-lines">Signature: <span class="quote-line"></span>Date: <span class="quote-line"></span></div><?php endif; ?></div>
                <div class="quote-amount"><div class="quote-amount-title">Amount Due</div><table><tr><td>Subtotal</td><td class="right"><?= doc_tpl_h($currency . ' ' . number_format($subtotal, 2)) ?></td></tr><tr><td>Tax 18%</td><td class="right"><?= doc_tpl_h($currency . ' ' . number_format($tax, 2)) ?></td></tr><tr><td><b>Total</b></td><td class="right"><b><?= doc_tpl_h($currency . ' ' . number_format($total, 2)) ?></b></td></tr></table><div class="quote-mark">Designed by <?= doc_tpl_h($companyName) ?></div></div>
            </section>
        </main>
    <?php else: ?>
        <main class="sheet receipt">
            <section class="receipt-head">
                <h1>Receipt</h1>
                <div class="receipt-company"><?= doc_tpl_h($companyName) ?><br><?= doc_tpl_h($companyAddress) ?><br><?= doc_tpl_h($companyPhone) ?></div>
            </section>
            <div class="receipt-meta"><div>Receipt Number<span class="receipt-line"><?= doc_tpl_h($template['number']) ?></span></div><div>Receipt Date<span class="receipt-line"><?= doc_tpl_h(date('Y-m-d')) ?></span></div></div>
            <div class="receipt-bar">Customer Details</div>
            <div class="receipt-fields">
                <div class="receipt-field"><span>Name</span><span class="receipt-line">Sample Customer Ltd</span></div>
                <div class="receipt-field"><span>Address</span><span class="receipt-line">Kampala Road, Uganda</span></div>
                <div class="receipt-field two"><span>Email</span><span class="receipt-line">accounts@customer.test</span><span>Phone</span><span class="receipt-line">+256 700 111 222</span></div>
            </div>
            <div class="receipt-bar">Products or Services</div>
            <table class="receipt-table">
                <thead><tr><th>Description</th><th>Price</th><th>Quantity</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php foreach ($sampleLines as $line): ?>
                        <tr><td><?= doc_tpl_h($line[0]) ?></td><td class="right"><?= doc_tpl_h(number_format((float) $line[2], 2)) ?></td><td class="right"><?= doc_tpl_h($line[1]) ?></td><td class="right"><?= doc_tpl_h(number_format((float) ($line[1] * $line[2]), 2)) ?></td></tr>
                    <?php endforeach; ?>
                    <?php for ($i = count($sampleLines); $i < 8; $i++): ?><tr><td></td><td></td><td></td><td></td></tr><?php endfor; ?>
                </tbody>
            </table>
            <section class="receipt-bottom">
                <div class="receipt-check">Payment Method:<br><span class="box"></span>Credit Card<br><span class="box"></span>Cash<br><span class="box"></span>Check<br><span class="box"></span>Mobile Money<br><small><?= doc_tpl_h($paymentMethodsNote) ?></small></div>
                <div><div class="receipt-total-row"><span>Sub Total</span><span><?= doc_tpl_h(number_format($subtotal, 2)) ?></span></div><div class="receipt-total-row"><span>Other</span><span></span></div><div class="receipt-total-row"><span>Taxes</span><span><?= doc_tpl_h(number_format($tax, 2)) ?></span></div><div class="receipt-total-row"><span>Grand Total</span><span><?= doc_tpl_h($currency . ' ' . number_format($total, 2)) ?></span></div></div>
            </section>
            <?php if ($showSignature): ?><div style="font-size:12px;margin-top:24px;">Received by: <span class="receipt-line" style="display:inline-block;width:220px;"></span></div><?php endif; ?>
            <div class="receipt-thanks">Thank you for your business!</div>
        </main>
    <?php endif; ?>
@include('layouts.design-lock')
</body>
</html>
    <?php
    exit;
}

$context = onyx_page_start('Document Templates', 'Document items for branded invoice, receipt, and quotation layouts.');
?>

<style>
    .template-page{display:grid;gap:16px}.template-actions{align-items:center;display:flex;flex-wrap:wrap;gap:8px}.template-chip{align-items:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:#d8d8de;display:inline-flex;font-size:10px;font-weight:800;gap:8px;min-height:34px;padding:0 11px;text-transform:uppercase}.template-list{display:grid;gap:12px}.template-item{align-items:center;background:var(--onyx-surface);border:1px solid var(--onyx-border);display:grid;gap:14px;grid-template-columns:46px minmax(0,1fr) auto;padding:14px}.template-icon{align-items:center;background:#fff;color:#050506;display:flex;height:42px;justify-content:center;width:42px}.template-copy strong{color:#fff;display:block;font-size:12px;font-weight:900;text-transform:uppercase}.template-copy span{color:var(--onyx-muted);display:block;font-size:11px;line-height:1.5;margin-top:4px}.template-meta{align-items:center;display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}.template-pill{border:1px solid rgba(255,255,255,.12);color:#d8d8de;display:inline-flex;font-size:10px;font-weight:800;min-height:28px;padding:0 8px;text-transform:uppercase}.template-buttons{display:flex;gap:8px;justify-content:flex-end}.template-btn{align-items:center;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.1);color:#fff;display:inline-flex;font-size:10px;font-weight:900;gap:7px;min-height:34px;padding:0 11px;text-decoration:none;text-transform:uppercase;white-space:nowrap}.template-btn.primary{background:#fff;color:#050506}.template-btn:hover{text-decoration:none}@media(max-width:760px){.template-item{grid-template-columns:1fr}.template-buttons{justify-content:flex-start}}
</style>

<div class="template-page">
    <div class="ops-strip">
        <div class="ops-card"><span>Invoice Template</span><strong><?= doc_tpl_h($templates['invoice']['number']) ?></strong></div>
        <div class="ops-card"><span>Receipt Template</span><strong><?= doc_tpl_h($templates['receipt']['number']) ?></strong></div>
        <div class="ops-card"><span>Quotation Template</span><strong><?= doc_tpl_h($templates['quotation']['number']) ?></strong></div>
        <div class="ops-card"><span>Branding</span><strong>Logo Ready</strong></div>
    </div>

    <div class="template-actions">
        <span class="template-chip"><i class="fa-solid fa-palette"></i> Branded layout</span>
        <span class="template-chip"><i class="fa-solid fa-eye"></i> Browser preview</span>
        <span class="template-chip"><i class="fa-solid fa-gear"></i> Uses document settings</span>
        <a class="ops-btn ghost" href="<?= doc_tpl_h(onyx_legacy_url('settings.php?section=documents')) ?>"><i class="fa-solid fa-sliders"></i> Document Settings</a>
    </div>

    <section class="template-list" aria-label="Document template items">
        <?php foreach ($templates as $template): ?>
            <article class="template-item">
                <div class="template-icon"><i class="fa-solid <?= doc_tpl_h($template['icon']) ?>"></i></div>
                <div class="template-copy">
                    <strong><?= doc_tpl_h($template['title']) ?></strong>
                    <span><?= doc_tpl_h($template['description']) ?></span>
                    <div class="template-meta">
                        <span class="template-pill"><?= doc_tpl_h($template['number']) ?></span>
                        <span class="template-pill"><?= doc_tpl_h($template['used_by']) ?></span>
                    </div>
                </div>
                <div class="template-buttons">
                    <a class="template-btn primary" target="_blank" href="<?= doc_tpl_h(onyx_legacy_url('document_templates.php?preview=' . $template['key'])) ?>"><i class="fa-solid fa-eye"></i> Preview</a>
                    <a class="template-btn" href="<?= doc_tpl_h(onyx_legacy_url('settings.php?section=documents')) ?>"><i class="fa-solid fa-sliders"></i> Settings</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<?php onyx_page_end(); ?>
