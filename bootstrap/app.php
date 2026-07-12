<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'dashboard', 'dashboard.php',
            'crm', 'crm.php',
            'customers', 'customers.php',
            'customers_action', 'customers_action.php',
            'suppliers', 'suppliers.php',
            'suppliers_action', 'suppliers_action.php',
            'products', 'products.php',
            'products_action', 'products_action.php',
            'inventory', 'inventory.php',
            'sales', 'sales.php',
            'sales_action', 'sales_action.php',
            'pos', 'pos.php',
            'purchases', 'purchases.php',
            'accounting', 'accounting.php',
            'banking', 'banking.php',
            'budgets', 'budgets.php',
            'assets', 'assets.php',
            'payroll', 'payroll.php',
            'reports', 'reports.php',
            'notifications', 'notifications.php',
            'settings', 'settings.php',
            'mobile_app', 'mobile_app.php',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
