<?php
/**
 * ONYX Accounting System - Tenant Self-Registration Gateway
 * Location: public/business/register.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$raw_tenant = $_GET['tenant_id'] ?? null;
$tenant_id = $raw_tenant ? preg_replace("/[^0-9]/", "", $raw_tenant) : null;

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Workspace - ONYX ACCOUNTING SYSTEM</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --onyx-bg: #12121a;
            --onyx-surface: #1c1c27;
            --onyx-accent: #ff6b00;
            --onyx-border: #2a2a3b;
            --onyx-text: #ffffff;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--onyx-bg);
            color: var(--onyx-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            margin: 0;
        }
        .onyx-card {
            background-color: var(--onyx-surface);
            border: 1px solid var(--onyx-border);
            border-radius: 10px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }
        .onyx-input {
            background-color: var(--onyx-bg) !important;
            border: 1px solid var(--onyx-border) !important;
            color: #ffffff !important;
            border-radius: 6px;
        }
        .onyx-input:focus {
            border-color: var(--onyx-accent) !important;
            box-shadow: none !important;
        }
        .btn-onyx {
            background-color: var(--onyx-accent);
            color: #ffffff;
            border: none;
            font-weight: 600;
            transition: background 0.2s ease;
        }
        .btn-onyx:hover {
            background-color: #e05e00;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="onyx-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-white mb-1" style="letter-spacing: 1px;">
                ONYX <span style="color: var(--onyx-accent);">ERP</span>
            </h2>
            <p class="text-muted small">Provision New Isolated Company Tenant Workspace</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small border-0 text-center bg-danger text-white mb-4 rounded">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="process_register.php" method="POST">
            
            <input type="hidden" name="parent_tenant_id" value="<?php echo htmlspecialchars((string)$tenant_id); ?>">

            <div class="mb-3">
                <label for="company_name" class="form-label small text-white-50">Company Corporate Name</label>
                <input type="text" class="form-control onyx-input" id="company_name" name="company_name" placeholder="Onyx Technology Solutions Ltd" required>
            </div>

            <div class="mb-3">
                <label for="currency" class="form-label small text-white-50">Primary Transaction Currency</label>
                <select class="form-select onyx-input" id="currency" name="currency">
                    <option value="UGX" selected>UGX - Ugandan Shilling</option>
                    <option value="KES">KES - Kenyan Shilling</option>
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                </select>
            </div>

            <hr class="my-4" style="border-color: var(--onyx-border);">

            <div class="mb-3">
                <label for="admin_name" class="form-label small text-white-50">Administrator Full Name</label>
                <input type="text" class="form-control onyx-input" id="admin_name" name="admin_name" placeholder="Steven John" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small text-white-50">System Email Address</label>
                <input type="email" class="form-control onyx-input" id="email" name="email" placeholder="admin@company.com" required>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="password" class="form-label small text-white-50">Account Passphrase</label>
                    <input type="password" class="form-control onyx-input" id="password" name="password" placeholder="••••••••" required>
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label small text-white-50">Confirm Passphrase</label>
                    <input type="password" class="form-control onyx-input" id="password_confirmation" name="password_confirmation" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-onyx w-100 py-2.5 text-uppercase tracking-wider">
                Provision Workspace Account
            </button>
            
        </form>

        <div class="text-center mt-4 pt-3 border-top border-secondary">
            <p class="text-muted small mb-0">
                Already have a workspace token? 
                <a href="login.php<?php echo $tenant_id ? "?tenant_id=".$tenant_id : ""; ?>" class="text-warning text-decoration-none opacity-75">Sign In Instead</a>
            </p>
        </div>
    </div>

</body>
</html>