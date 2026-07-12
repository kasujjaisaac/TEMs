<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* Sidebar Container */
    .sidebar {
        width: 220px;
        background: linear-gradient(135deg, var(--gray-900) 0%, #0a0f1f 100%);
        border-right: 1px solid rgba(255,255,255,0.05);
        padding: 0;
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        overflow-y: auto;
        z-index: 1000;
    }

    /* Logo Section */
    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        margin-bottom: 10px;
    }

    .sidebar-logo-icon {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        background: linear-gradient(135deg, var(--accent-gold), #e6a900);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #000;
        font-size: 18px;
    }

    .sidebar-logo-text {
        font-weight: 800;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    .sidebar-logo-text small {
        display: block;
        font-size: 9px;
        color: var(--text-muted);
        font-weight: 600;
    }

    /* Navigation Groups */
    .sidebar-section {
        display: flex;
        flex-direction: column;
    }

    .sidebar-section-title {
        font-size: 8px;
        font-weight: 800;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 15px 15px 10px 15px;
        display: block;
    }

    /* Navigation Links */
    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s;
        border-left: 3px solid transparent;
        margin: 0 10px 0 0;
        position: relative;
    }

    .sidebar-link i {
        font-size: 15px;
        width: 20px;
        text-align: center;
    }

    .sidebar-link:hover {
        color: #fff;
        background: rgba(255,255,255,0.05);
    }

    .sidebar-link.active {
        color: var(--accent-gold);
        background: rgba(241, 196, 15, 0.1);
        border-left-color: var(--accent-gold);
        font-weight: 600;
    }

    /* Collapsible Groups */
    .sidebar-group {
        display: flex;
        flex-direction: column;
    }

    .sidebar-group-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 15px;
        color: var(--text-muted);
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s;
        border-left: 3px solid transparent;
        margin: 0 10px 0 0;
        cursor: pointer;
        background: none;
        border: none;
        width: calc(100% - 10px);
        text-align: left;
    }

    .sidebar-group-toggle i:first-child {
        font-size: 15px;
        width: 20px;
        text-align: center;
    }

    .sidebar-group-toggle i:last-child {
        font-size: 12px;
        transition: transform 0.3s;
    }

    .sidebar-group-toggle:hover {
        color: #fff;
        background: rgba(255,255,255,0.05);
    }

    .sidebar-group-toggle.active {
        color: var(--accent-gold);
        background: rgba(241, 196, 15, 0.1);
        border-left-color: var(--accent-gold);
        font-weight: 600;
    }

    .sidebar-group-toggle.active i:last-child {
        transform: rotate(180deg);
    }

    /* Submenu */
    .sidebar-submenu {
        display: none;
        flex-direction: column;
        gap: 0;
        background: rgba(0,0,0,0.3);
        border-left: 1px solid rgba(255,255,255,0.05);
        margin-left: 10px;
    }

    .sidebar-submenu.show {
        display: flex;
    }

    .sidebar-submenu .sidebar-link {
        padding: 10px 15px 10px 35px;
        font-size: 12px;
        margin: 0;
        border-left: none;
    }

    /* Account Section */
    .sidebar-account {
        margin-top: auto;
        border-top: 1px solid rgba(255,255,255,0.05);
        padding: 10px 0;
        display: flex;
        flex-direction: column;
    }

    /* Scrollbar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.1);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255,255,255,0.2);
    }

    /* Adjust main content for sidebar */
    body {
        display: flex;
    }

    #main {
        margin-left: 220px;
        flex: 1;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
            transform: translateX(0);
        }

        .sidebar-logo-text,
        .sidebar-link span,
        .sidebar-section-title,
        .sidebar-group-toggle span {
            display: none;
        }

        .sidebar-link,
        .sidebar-group-toggle {
            justify-content: center;
            padding: 12px;
            margin: 0;
        }

        .sidebar-link i,
        .sidebar-group-toggle i:first-child {
            width: auto;
            font-size: 18px;
        }

        .sidebar-link:hover,
        .sidebar-group-toggle:hover {
            background: rgba(241, 196, 15, 0.2);
        }

        .sidebar-submenu {
            display: none !important;
        }

        #main {
            margin-left: 70px;
        }
    }
</style>

<div class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">O</div>
        <div class="sidebar-logo-text">
            Onyx <small>Hub</small>
        </div>
    </div>

    <!-- Main Navigation -->
    <div class="sidebar-section">
        <nav class="sidebar-nav">
            <a href="dashboard.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="router.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'router.php' ? 'active' : ''; ?>">
                <i class="fas fa-router"></i>
                <span>Router</span>
            </a>
            <a href="usage_analytics.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'usage_analytics.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Usage Analytics</span>
            </a>
            <a href="my_inbox.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'my_inbox.php' ? 'active' : ''; ?>">
                <i class="fas fa-headset"></i>
                <span>My Inbox</span>
            </a>
        </nav>
    </div>

    <!-- Hotspot Management Section -->
    <div class="sidebar-section">
        <span class="sidebar-section-title">Hotspot Mgmt</span>
        <nav class="sidebar-nav">
            <button class="sidebar-group-toggle" data-toggle="hotspot-group">
                <span><i class="fas fa-wifi"></i> <span>Sales</span></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="sidebar-submenu" id="hotspot-group">
                <a href="hotspot.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'hotspot.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> <span>Overview</span>
                </a>
                <a href="hotspot_setup.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'hotspot_setup.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i> <span>Setup</span>
                </a>
                <a href="hotspot_packages.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'hotspot_packages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> <span>Packages</span>
                </a>
                <a href="hotspot_vouchers.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'hotspot_vouchers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i> <span>Vouchers</span>
                </a>
                <a href="hotspot_users.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'hotspot_users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <span>Hotspot Users</span>
                </a>
            </div>

            <button class="sidebar-group-toggle" data-toggle="float-group">
                <span><i class="fas fa-square-watermark"></i> <span>Float</span></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="sidebar-submenu" id="float-group">
                <a href="float_overview.php<?php echo $nav_query; ?>" class="sidebar-link">
                    <i class="fas fa-th-large"></i> <span>Overview</span>
                </a>
                <a href="float_setup.php<?php echo $nav_query; ?>" class="sidebar-link">
                    <i class="fas fa-tools"></i> <span>Setup</span>
                </a>
            </div>

            <a href="pppoe.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'pppoe.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Pppoe Users</span>
            </a>

            <a href="packages.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'packages.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                <span>Packages</span>
            </a>

            <a href="transactions.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'transactions.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i>
                <span>Transactions</span>
            </a>

            <a href="disbursements.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'disbursements.php' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Disbursements</span>
            </a>

            <a href="agent_pos.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'agent_pos.php' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i>
                <span>Agent PoS</span>
            </a>

            <a href="vouchers.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'vouchers.php' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i>
                <span>Vouchers</span>
            </a>

            <a href="remote_access.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'remote_access.php' ? 'active' : ''; ?>">
                <i class="fas fa-network-wired"></i>
                <span>Remote Access</span>
            </a>

            <button class="sidebar-group-toggle" data-toggle="settings-group">
                <span><i class="fas fa-cog"></i> <span>Settings</span></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="sidebar-submenu" id="settings-group">
                <a href="settings.php<?php echo $nav_query; ?>#company" class="sidebar-link">
                    <i class="fas fa-building"></i> <span>Company</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#accounting" class="sidebar-link">
                    <i class="fas fa-calculator"></i> <span>Accounting</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#tax" class="sidebar-link">
                    <i class="fas fa-percent"></i> <span>Tax</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#users_roles" class="sidebar-link">
                    <i class="fas fa-users-cog"></i> <span>Users & Roles</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#branches" class="sidebar-link">
                    <i class="fas fa-code-branch"></i> <span>Branches</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#warehouses" class="sidebar-link">
                    <i class="fas fa-warehouse"></i> <span>Warehouses</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#products" class="sidebar-link">
                    <i class="fas fa-box-open"></i> <span>Products</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#payment_methods" class="sidebar-link">
                    <i class="fas fa-credit-card"></i> <span>Payment Methods</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#invoice_settings" class="sidebar-link">
                    <i class="fas fa-file-invoice-dollar"></i> <span>Invoice Settings</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#receipt_settings" class="sidebar-link">
                    <i class="fas fa-receipt"></i> <span>Receipt Settings</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#quotation_settings" class="sidebar-link">
                    <i class="fas fa-file-signature"></i> <span>Quotation Settings</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#notifications" class="sidebar-link">
                    <i class="fas fa-bell"></i> <span>Notifications</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#email_smtp" class="sidebar-link">
                    <i class="fas fa-envelope"></i> <span>Email (SMTP)</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#security" class="sidebar-link">
                    <i class="fas fa-shield-alt"></i> <span>Security</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#audit_logs" class="sidebar-link">
                    <i class="fas fa-clipboard-list"></i> <span>Audit Logs</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#financial_periods" class="sidebar-link">
                    <i class="fas fa-calendar-check"></i> <span>Financial Periods</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#backup_restore" class="sidebar-link">
                    <i class="fas fa-database"></i> <span>Backup & Restore</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#import_export" class="sidebar-link">
                    <i class="fas fa-file-import"></i> <span>Import & Export</span>
                </a>
                <a href="settings.php<?php echo $nav_query; ?>#system_info" class="sidebar-link">
                    <i class="fas fa-info-circle"></i> <span>System Information</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Account Section -->
    <div class="sidebar-section sidebar-account">
        <span class="sidebar-section-title">Account</span>
        <nav class="sidebar-nav">
            <button class="sidebar-group-toggle" data-toggle="account-group">
                <span><i class="fas fa-wallet"></i> <span>Billing</span></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="sidebar-submenu" id="account-group">
                <a href="billing.php<?php echo $nav_query; ?>" class="sidebar-link">
                    <i class="fas fa-receipt"></i> <span>Invoices</span>
                </a>
                <a href="billing_history.php<?php echo $nav_query; ?>" class="sidebar-link">
                    <i class="fas fa-history"></i> <span>History</span>
                </a>
            </div>

            <button class="sidebar-group-toggle" data-toggle="limits-group">
                <span><i class="fas fa-shield-alt"></i> <span>Features & Limits</span></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="sidebar-submenu" id="limits-group">
                <a href="features.php<?php echo $nav_query; ?>" class="sidebar-link">
                    <i class="fas fa-star"></i> <span>Features</span>
                </a>
            </div>

            <a href="support.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'support.php' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i>
                <span>Support</span>
            </a>
        </nav>
    </div>
    
                <a href="logout.php<?php echo $nav_query; ?>" class="sidebar-link <?php echo $current_page === 'logout.php' ? 'active' : ''; ?>">
                <i class="fas fa-question-circle"></i>
                <span>Logout</span>
            </a>
</div>

<script>
    // Collapsible menu functionality (scoped to this sidebar)
    (function() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        sidebar.querySelectorAll('.sidebar-group-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-toggle');
                const submenu = sidebar.querySelector('#' + (window.CSS && CSS.escape ? CSS.escape(targetId) : targetId));
                const isActive = this.classList.contains('active');

                // Close other menus within this sidebar only
                sidebar.querySelectorAll('.sidebar-submenu').forEach(menu => {
                    menu.classList.remove('show');
                });
                sidebar.querySelectorAll('.sidebar-group-toggle').forEach(btn => {
                    btn.classList.remove('active');
                });

                // Toggle current menu
                if (!isActive && submenu) {
                    submenu.classList.add('show');
                    this.classList.add('active');
                }
            });
        });

        // Auto-open active group inside this sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const activeLink = sidebar.querySelector('.sidebar-link.active');
            if (activeLink) {
                const parent = activeLink.closest('.sidebar-submenu');
                if (parent) {
                    parent.classList.add('show');
                    const toggle = parent.previousElementSibling;
                    if (toggle && toggle.classList.contains('sidebar-group-toggle')) {
                        toggle.classList.add('active');
                    }
                }
            }
        });
    })();
</script>