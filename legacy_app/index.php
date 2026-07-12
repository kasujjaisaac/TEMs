<?php
/**
 * ONYX Accounting System - Marketing Landing Page
 * Location: public/business/index.php
 */

// Initialize session parameters if tenant is already passed down, otherwise default
$raw_tenant = $_GET['tenant_id'] ?? null;
$tenant_id = $raw_tenant ? preg_replace("/[^0-9]/", "", $raw_tenant) : null;
$nav_query = $tenant_id ? "?tenant_id=" . $tenant_id . "&" : "?";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Onyx Accounting System</title>
    <meta name="description" content="Onyx Accounting gives modern businesses complete control of their invoicing, expense categorization, real-time dashboards, and automated printable performance reports. Built for growth.">

    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <meta name="theme-color" content="#4f46e5">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7fafc; }
        .pricing-card { transition: transform 0.3s ease; }
        .pricing-card:hover { transform: translateY(-5px); }
        .feature-card { transition: box-shadow 0.3s ease; }
        .feature-card:hover { box-shadow: 0 10px 30px rgba(79,70,229,0.12); }
        .audience-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .audience-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="text-gray-800">

    <header class="bg-white shadow-sm py-4 px-6 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center space-x-2 cursor-pointer" onclick="window.location.href='index.php<?php echo $tenant_id ? "?tenant_id=" . $tenant_id : ""; ?>'">
            <div class="w-8 h-8 bg-indigo-600 rounded flex items-center justify-center text-white font-bold text-xl">🧮</div>
            <span class="font-bold text-lg text-gray-900">Onyx Accounting System</span>
        </div>
        <nav class="hidden md:flex items-center space-x-6">
            <a href="#features" class="text-gray-600 hover:text-indigo-600 font-medium transition">Features</a>
            <a href="#who-its-for" class="text-gray-600 hover:text-indigo-600 font-medium transition">Who It's For</a>
            <a href="#pricing" class="text-gray-600 hover:text-indigo-600 font-medium transition">Pricing</a>
            <a href="login.php<?php echo $nav_query; ?>" class="text-gray-600 hover:text-indigo-600 font-medium transition">Login</a>
            <a href="register.php<?php echo $nav_query; ?>" class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">Get Started Free</a>
        </nav>
        <button class="md:hidden text-gray-600 focus:outline-none" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </header>
    
    <div id="mobile-menu" class="hidden bg-white border-t border-gray-100 px-6 py-4 space-y-3 md:hidden">
        <a href="#features" class="block text-gray-700 font-medium hover:text-indigo-600">Features</a>
        <a href="#who-its-for" class="block text-gray-700 font-medium hover:text-indigo-600">Who It's For</a>
        <a href="#pricing" class="block text-gray-700 font-medium hover:text-indigo-600">Pricing</a>
        <a href="login.php<?php echo $nav_query; ?>" class="block text-gray-700 font-medium hover:text-indigo-600">Login</a>
        <a href="register.php<?php echo $nav_query; ?>" class="block bg-indigo-600 text-white text-center px-5 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">Get Started Free</a>
    </div>

    <main>

        <section class="bg-gradient-to-br from-indigo-50 via-white to-indigo-50 py-24 px-4">
            <div class="container mx-auto text-center max-w-4xl">
                <span class="inline-block bg-indigo-100 text-indigo-700 text-sm font-semibold px-4 py-1 rounded-full mb-6 tracking-wide">Multi-Tenant Corporate Financial Suite</span>
                <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 mb-6 tracking-tight leading-tight">
                    Take Control of Your Books.<br class="hidden md:block"> Clear Insights. Real Growth.
                </h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
                    Onyx Accounting is the ultimate financial tracking and cash management hub. Automate quotations, issue custom invoices, record categorized incomes/expenses, and view accurate performance updates in real-time.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="register.php<?php echo $nav_query; ?>"
                       class="bg-indigo-600 text-white font-semibold py-4 px-10 rounded-full shadow-lg hover:bg-indigo-700 transition transform hover:scale-105 inline-block w-full sm:w-auto">
                        Start Your 7-Day Free Trial
                    </a>
                    <a href="mailto:sales@onyxtechpay.com"
                       class="bg-white text-indigo-600 border border-indigo-200 font-semibold py-4 px-10 rounded-full shadow hover:bg-indigo-50 transition inline-block w-full sm:w-auto">
                        <i class="fas fa-calendar-alt mr-2"></i>Book a Demo
                    </a>
                </div>
                <p class="text-sm text-gray-400 mt-4">No credit card required. Cancel anytime.</p>
            </div>
        </section>

        <section class="bg-white border-y border-gray-100 py-6 px-4">
            <div class="container mx-auto text-center">
                <p class="text-gray-500 text-sm font-medium uppercase tracking-widest">
                    Trusted by modern business operators, SaaS teams, and retail stores across the region
                </p>
            </div>
        </section>

        <section class="py-20 px-4 bg-white">
            <div class="container mx-auto max-w-4xl text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                    Financial Management Shouldn't Take Up All Your Time
                </h2>
                <p class="text-lg text-gray-600 leading-relaxed mb-6">
                    Most companies lose visibility over their exact daily margins. Uncategorized cash outflows drain resources, invoices fall past due without notices, and preparing printable tax or audit sheets takes days of manual calculations.
                </p>
                <p class="text-lg text-gray-600 leading-relaxed">
                    Onyx Accounting shifts the balance. Built with performance isolation architecture, it gives you clean multi-tenant record keeping without database clutter. Manage product inventories, track separate categorized revenue streams, and run calculations seamlessly on any host.
                </p>
            </div>
        </section>

        <section id="features" class="py-20 px-4 bg-indigo-50">
            <div class="container mx-auto max-w-6xl">
                <div class="text-center mb-14">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Everything You Need to Run a Compliant Ledger</h2>
                    <p class="text-gray-600 max-w-xl mx-auto text-lg">One unified ledger. Total control. Zero spreadsheets.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="feature-card bg-white rounded-xl p-7 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-file-invoice text-indigo-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Invoices &amp; Cash Sales</h3>
                        <p class="text-gray-600 leading-relaxed">Generate instant receipts, process cash sales directly at checkout, and create clean balance estimates on demand.</p>
                    </div>
                    <div class="feature-card bg-white rounded-xl p-7 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-receipt text-indigo-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Expense Categorization</h3>
                        <p class="text-gray-600 leading-relaxed">Sort operations expenses against clear budget lines. Know exactly where capital drops, with error-free categorization pools.</p>
                    </div>
                    <div class="feature-card bg-white rounded-xl p-7 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-chart-pie text-indigo-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Real-Time Dashboards</h3>
                        <p class="text-gray-600 leading-relaxed">See your net profit margin metrics update as soon as sales complete. Trace transactions across isolated customer accounts.</p>
                    </div>
                    <div class="feature-card bg-white rounded-xl p-7 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-print text-indigo-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Detailed PDF Reporting</h3>
                        <p class="text-gray-600 leading-relaxed">Download formal performance sheets grouped for daily, weekly, monthly, and yearly reviews at the click of a button.</p>
                    </div>
                    <div class="feature-card bg-white rounded-xl p-7 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-boxes text-indigo-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Categorized Products</h3>
                        <p class="text-gray-600 leading-relaxed">Map your stock inventory entries directly to their accurate income channels for unified tracking statements.</p>
                    </div>
                    <div class="feature-card bg-white rounded-xl p-7 shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-users-cog text-indigo-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Multi-Tenant Control</h3>
                        <p class="text-gray-600 leading-relaxed">Keep absolute operational boundaries. Securely separate accounting entries while sharing standard layouts and navigation items.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="who-its-for" class="py-20 px-4 bg-white">
            <div class="container mx-auto max-w-6xl">
                <div class="text-center mb-14">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Engineered for Businesses Focused on Profitability</h2>
                    <p class="text-gray-600 max-w-xl mx-auto text-lg">Whether you handle service bookings, retail inventories, or supplier channels, Onyx adapts to your workflows.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="audience-card bg-indigo-50 rounded-xl p-7 border border-indigo-100">
                        <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-store text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Retail &amp; POS Shops</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Log immediate cash sales, manage catalog groupings, and balance cash drawers with systematic reporting.</p>
                    </div>
                    <div class="audience-card bg-indigo-50 rounded-xl p-7 border border-indigo-100">
                        <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-laptop-code text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">SaaS &amp; Tech Services</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Automate invoicing, separate recurrent collections, and match income types with precision insights.</p>
                    </div>
                    <div class="audience-card bg-indigo-50 rounded-xl p-7 border border-indigo-100">
                        <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-truck-moving text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Suppliers &amp; Traders</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Maintain outstanding balance sheets, track wholesale extending credit limits, and review supplier logs.</p>
                    </div>
                    <div class="audience-card bg-indigo-50 rounded-xl p-7 border border-indigo-100">
                        <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-5">
                            <i class="fas fa-briefcase text-white text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Consultants &amp; Agencies</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Draft quick professional estimates and quotations, compile business expense rows, and audit project metrics.</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="py-20 px-4 bg-white border-t border-gray-100">
            <div class="container mx-auto max-w-6xl">
                <div class="text-center mb-14">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Simple, Transparent Plans</h2>
                    <p class="text-gray-600 max-w-xl mx-auto text-lg">Every account includes a 7-day free trial. Pick a plan that grows with you.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                    <div class="bg-white rounded-xl shadow-lg p-8 border-t-4 border-indigo-400 pricing-card flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-900 mb-1">Growth</h3>
                        <p class="text-gray-500 mb-5 text-sm">Perfect for single owners and growing enterprises</p>
                        <div class="flex items-baseline mb-6">
                            <span class="text-5xl font-extrabold text-gray-900">100k</span>
                            <span class="text-xl font-semibold text-gray-500 ml-1">UGX /month</span>
                        </div>
                        <ul class="text-gray-700 space-y-3 mb-8 flex-grow">
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Full Access to Invoices &amp; Estimates</li>
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Expense &amp; Income Categorization</li>
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Printable Performance PDF Reports</li>
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Standard Analytical Dashboard</li>
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>7-Day Free Trial</li>
                        </ul>
                        <a href="register.php<?php echo $nav_query; ?>" class="block bg-indigo-600 text-white text-center font-bold py-3 rounded-lg hover:bg-indigo-700 transition">
                            Start Free Trial
                        </a>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-8 border-t-4 border-indigo-600 pricing-card flex flex-col">
                        <h3 class="text-2xl font-bold text-gray-900 mb-1">Enterprise</h3>
                        <p class="text-gray-500 mb-5 text-sm">For complex teams and heavy inventory channels</p>
                        <div class="flex items-baseline mb-6">
                            <span class="text-5xl font-extrabold text-gray-900">Custom</span>
                        </div>
                        <ul class="text-gray-700 space-y-3 mb-8 flex-grow">
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Everything in Growth</li>
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Multi-user Permission Controls</li>
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Bulk Data Spreadsheet Exports</li>
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Priority Server Allocation (Hostinger Optimized)</li>
                            <li><i class="fas fa-check text-indigo-500 mr-2"></i>Dedicated Account Setup Support</li>
                        </ul>
                        <a href="mailto:sales@onyxtechpay.com" class="block bg-gray-900 text-white text-center font-bold py-3 rounded-lg hover:bg-black transition">
                            Contact Sales
                        </a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-gray-900 text-gray-400 py-12 px-6">
        <div class="container mx-auto max-w-6xl grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
            <div>
                <div class="flex items-center space-x-2 mb-3">
                    <div class="text-xl">🧮</div>
                    <span class="font-bold text-white text-lg">Onyx Accounting</span>
                </div>
                <p class="text-sm leading-relaxed">Multi-tenant business management and accounting framework engines. Developed by Onyx Technology Solutions Ltd.</p>
            </div>
            <div>
                <p class="text-white font-semibold mb-3">Quick Links</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="#features" class="hover:text-white transition">Features</a></li>
                    <li><a href="#who-its-for" class="hover:text-white transition">Who It's For</a></li>
                    <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
                    <li><a href="login.php<?php echo $nav_query; ?>" class="hover:text-white transition">Login Workspace</a></li>
                    <li><a href="register.php<?php echo $nav_query; ?>" class="hover:text-white transition">Register Account</a></li>
                </ul>
            </div>
            <div>
                <p class="text-white font-semibold mb-3">Get in Touch</p>
                <ul class="space-y-2 text-sm">
                    <li><a href="mailto:sales@onyxtechpay.com" class="hover:text-white transition"><i class="fas fa-envelope mr-2"></i>sales@onyxtechpay.com</a></li>
                    <li><a href="mailto:support@onyxtechpay.com" class="hover:text-white transition"><i class="fas fa-headset mr-2"></i>support@onyxtechpay.com</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-800 pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-sm">&copy; 2026 Onyx Technology Solutions Ltd. All rights reserved.</p>
            <div class="flex space-x-6 text-sm">
                <a href="#" class="hover:text-white transition">Privacy Policy</a>
                <a href="#" class="hover:text-white transition">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll implementation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</body>
</html>