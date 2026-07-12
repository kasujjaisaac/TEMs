<?php
/**
 * ONYX Accounting System - Dynamic Gateway Login
 * Location: public/business/login.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Catch incoming parameters from your landing page nav query
$raw_tenant = $_GET['tenant_id'] ?? null;
$tenant_id = $raw_tenant ? preg_replace("/[^0-9]/", "", $raw_tenant) : null;
$tenant_slug = trim($_GET['tenant_slug'] ?? '');

// Operational alert flags
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ONYX ERP</title>
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
            padding: 15px;
            margin: 0;
        }
        .onyx-card {
            background-color: var(--onyx-surface);
            border: 1px solid var(--onyx-border);
            border-radius: 10px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
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
            <p class="text-muted small">Multi-Tenant Corporate Accounting Gateway</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small border-0 text-center bg-danger text-white mb-4 rounded">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-2 small border-0 text-center bg-success text-white mb-4 rounded">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="process_login.php" method="POST">
            
            <input type="hidden" name="tenant_id" value="<?php echo htmlspecialchars((string)$tenant_id); ?>">

            <div class="mb-3">
                <label for="tenant_slug" class="form-label small text-white-50">Company Workspace Handle</label>
                <input type="text" 
                       class="form-control onyx-input" 
                       id="tenant_slug" 
                       name="tenant_slug" 
                       placeholder="e.g. onyx-tech" 
                       value="<?php echo htmlspecialchars($tenant_slug); ?>"
                       required autocomplete="off">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small text-white-50">Enterprise Email Address</label>
                <input type="email" 
                       class="form-control onyx-input" 
                       id="email" 
                       name="email" 
                       placeholder="name@company.com" 
                       required autocomplete="email">
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small text-white-50">Secure Passphrase</label>
                <input type="password" 
                       class="form-control onyx-input" 
                       id="password" 
                       name="password" 
                       placeholder="••••••••" 
                       required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-onyx w-100 py-2.5 text-uppercase tracking-wider">
                Unlock Workspace
            </button>
            
        </form>

        <div class="text-center mt-4 pt-3 border-top border-secondary">
            <p class="text-muted small mb-0">
                Don't have an account? 
                <a href="register.php<?php echo $tenant_id ? "?tenant_id=".$tenant_id : ""; ?>" class="text-warning text-decoration-none opacity-75">Register Here</a>
            </p>
        </div>
    </div>

</body>
</html>
