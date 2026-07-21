<style id="tems-design-lock">
    :root {
        --accent: #ffffff !important;
        --accent-600: #d8d8de !important;
        --onyx-accent: #ffffff !important;
        --onyx-accent-2: #d8d8de !important;
        --onyx-bg: #050506 !important;
        --onyx-surface: #121216 !important;
        --onyx-surface-2: #17171c !important;
        --onyx-border: rgba(255,255,255,.14) !important;
        --onyx-border-strong: rgba(255,255,255,.52) !important;
        --onyx-text: #f7f7fa !important;
        --onyx-muted: #a6a2bc !important;
        --onyx-muted-2: #c6c1dc !important;
    }

    * {
        border-radius: 0 !important;
    }

    body,
    .onyx-erp-body {
        background:
            linear-gradient(rgba(255,255,255,.026) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px),
            linear-gradient(180deg, #050506 0%, #0b0b10 50%, #050506 100%) !important;
        background-size: 44px 44px, 44px 44px, auto !important;
        color: var(--onyx-text) !important;
    }

    .page-header,
    .tems-command-header,
    .commercial-header,
    .finance-header,
    .planning-header,
    .hr-core-header,
    .access-header,
    .admin-header,
    .hr-hero,
    .contract-hero,
    .employee-hero,
    .dash-hero,
    .sales-hero,
    .sales-toolbar,
    .supplier-toolbar,
    .purchase-toolbar,
    .product-toolbar,
    .customer-toolbar,
    .admin-hero {
        align-items: center !important;
        background: linear-gradient(180deg, rgba(255,255,255,.055), rgba(255,255,255,.018)) !important;
        border: 1px solid var(--onyx-border) !important;
        box-shadow: none !important;
        margin-bottom: 12px !important;
        min-height: 58px !important;
        padding: 10px 14px !important;
    }

    .stat-grid,
    .dashboard-grid,
    .tems-kpi-grid,
    .commercial-grid,
    .finance-grid,
    .planning-grid,
    .hr-core-grid,
    .access-kpis,
    .admin-kpis,
    .admin-card-grid,
    .hr-stats,
    .contract-stats,
    .employee-stats,
    .inventory-kpis,
    .purchase-kpis,
    .supplier-kpis,
    .product-kpis,
    .product-summary-grid,
    .crm-grid,
    .dash-kpis,
    .bottom-kpis,
    .sales-summary-grid,
    .sales-report-grid,
    .ops-grid,
    .report-grid {
        display: grid !important;
        gap: 8px !important;
        grid-auto-rows: max-content !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    }

    .card,
    .panel,
    .stat-card,
    .tems-kpi-card,
    .tems-panel,
    .tems-work-card,
    .tems-insight-card,
    .commercial-card,
    .commercial-panel,
    .finance-card,
    .finance-panel,
    .planning-card,
    .planning-panel,
    .hr-core-card,
    .hr-core-panel,
    .access-kpi,
    .access-panel,
    .admin-kpi,
    .admin-card,
    .admin-panel,
    .hr-stat,
    .hr-panel,
    .contract-stat,
    .contract-panel,
    .employee-stat,
    .employee-panel,
    .inventory-kpi,
    .inventory-panel,
    .purchase-kpi,
    .purchase-panel,
    .supplier-kpi,
    .supplier-panel,
    .customer-card,
    .customer-panel,
    .crm-kpi,
    .crm-panel,
    .product-kpi,
    .product-panel,
    .product-summary-card,
    .dash-kpi,
    .dash-panel,
    .bottom-card,
    .sales-metric,
    .sales-panel,
    .sales-report-card,
    .pos-stat,
    .pos-panel,
    .template-card,
    .template-panel,
    .preview-shell,
    .bank-panel,
    .budget-panel,
    .asset-panel,
    .report-panel,
    .notification-panel,
    .mobile-panel,
    .ops-card,
    .ops-report-card,
    .role-card {
        background: linear-gradient(180deg, rgba(255,255,255,.052), rgba(255,255,255,.018)) !important;
        border: 1px solid var(--onyx-border) !important;
        box-shadow: none !important;
        color: var(--onyx-text) !important;
        min-height: 0 !important;
        padding: 10px !important;
    }

    .stat-card,
    .tems-kpi-card,
    .commercial-card,
    .finance-card,
    .planning-card,
    .hr-core-card,
    .access-kpi,
    .admin-kpi,
    .hr-stat,
    .contract-stat,
    .employee-stat,
    .inventory-kpi,
    .purchase-kpi,
    .supplier-kpi,
    .crm-kpi,
    .product-kpi,
    .dash-kpi,
    .bottom-card,
    .sales-metric,
    .pos-stat {
        min-height: 62px !important;
        padding: 9px 10px !important;
    }

    .stat-label,
    .stat-card .label,
    .tems-kpi-card span,
    .commercial-card span,
    .finance-card span,
    .planning-card span,
    .hr-core-card span,
    .access-kpi span,
    .admin-kpi span,
    .hr-stat span,
    .contract-stat span,
    .employee-stat span,
    .inventory-kpi span,
    .purchase-kpi span,
    .supplier-kpi span,
    .crm-kpi span,
    .product-kpi span,
    .dash-kpi span,
    .bottom-card span,
    .sales-metric span,
    .panel-title,
    .tems-panel-kicker,
    .sales-section-title {
        color: var(--onyx-muted) !important;
        font-size: 9px !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
        line-height: 1.1 !important;
        text-transform: uppercase !important;
    }

    .stat-value,
    .stat-card .value,
    .tems-kpi-card strong,
    .commercial-card strong,
    .finance-card strong,
    .planning-card strong,
    .hr-core-card strong,
    .access-kpi strong,
    .admin-kpi strong,
    .hr-stat strong,
    .contract-stat strong,
    .employee-stat strong,
    .inventory-kpi strong,
    .purchase-kpi strong,
    .supplier-kpi strong,
    .crm-kpi strong,
    .product-kpi strong,
    .dash-kpi strong,
    .bottom-card strong,
    .sales-metric strong {
        color: #ffffff !important;
        display: block !important;
        font-size: 18px !important;
        font-weight: 900 !important;
        line-height: 1 !important;
        margin-top: 5px !important;
        overflow-wrap: anywhere !important;
    }

    .btn,
    .action-btn,
    .tems-button,
    .commercial-button,
    .finance-button,
    .planning-button,
    .hr-core-button,
    .access-button,
    .admin-btn,
    .hr-btn,
    .contract-btn,
    .employee-btn,
    .inventory-btn,
    .purchase-btn,
    .supplier-btn,
    .customer-action,
    .customer-form-btn,
    .crm-btn,
    .product-action,
    .product-btn,
    .sales-action,
    .sales-tab,
    .pos-btn,
    .template-btn,
    .preview-btn,
    button[type="submit"] {
        align-items: center !important;
        background: #ffffff !important;
        border: 1px solid #ffffff !important;
        color: #050506 !important;
        display: inline-flex !important;
        font-size: 10px !important;
        font-weight: 900 !important;
        gap: 7px !important;
        justify-content: center !important;
        min-height: 34px !important;
        padding: 0 11px !important;
        text-transform: uppercase !important;
        white-space: nowrap !important;
    }

    .secondary,
    .btn.secondary,
    .btn.ghost,
    .commercial-button.secondary,
    .finance-button.secondary,
    .planning-button.secondary,
    .tems-button.secondary,
    .sales-action:not(.primary),
    .sales-tab:not(.active),
    .admin-btn.secondary,
    .employee-btn.secondary,
    .supplier-btn.secondary,
    .purchase-btn.secondary,
    .product-btn.secondary,
    .crm-btn.secondary {
        background: transparent !important;
        border-color: var(--onyx-border-strong) !important;
        color: #ffffff !important;
    }

    .badge,
    .tems-status,
    .commercial-badge,
    .finance-badge,
    .planning-badge,
    .access-badge,
    .admin-badge,
    .hr-status,
    .contract-status,
    .employee-status,
    .supplier-badge,
    .purchase-badge,
    .sales-badge,
    .dash-status {
        background: rgba(255,255,255,.08) !important;
        border: 1px solid rgba(255,255,255,.35) !important;
        color: #ffffff !important;
        display: inline-flex !important;
        font-size: 9px !important;
        font-weight: 900 !important;
        min-height: 24px !important;
        padding: 0 8px !important;
        text-transform: uppercase !important;
    }

    table th,
    table td,
    .table th,
    .table td,
    .commercial-table th,
    .commercial-table td,
    .finance-table th,
    .finance-table td,
    .sales-table th,
    .sales-table td,
    .purchase-table th,
    .purchase-table td,
    .supplier-table th,
    .supplier-table td,
    .admin-table th,
    .admin-table td,
    .pos-table th,
    .pos-table td {
        font-size: 10px !important;
        padding: 8px 9px !important;
    }

    table th,
    .table th,
    .commercial-table th,
    .finance-table th,
    .sales-table th,
    .purchase-table th,
    .supplier-table th,
    .admin-table th,
    .pos-table th {
        color: var(--onyx-muted) !important;
        font-size: 9px !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
    }

    @media (max-width: 1100px) {
        .stat-grid,
        .dashboard-grid,
        .tems-kpi-grid,
        .commercial-grid,
        .finance-grid,
        .planning-grid,
        .hr-core-grid,
        .access-kpis,
        .admin-kpis,
        .admin-card-grid,
        .hr-stats,
        .contract-stats,
        .employee-stats,
        .inventory-kpis,
        .purchase-kpis,
        .supplier-kpis,
        .product-kpis,
        .crm-grid,
        .sales-summary-grid,
        .sales-report-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 720px) {
        .stat-grid,
        .dashboard-grid,
        .tems-kpi-grid,
        .commercial-grid,
        .finance-grid,
        .planning-grid,
        .hr-core-grid,
        .access-kpis,
        .admin-kpis,
        .admin-card-grid,
        .hr-stats,
        .contract-stats,
        .employee-stats,
        .inventory-kpis,
        .purchase-kpis,
        .supplier-kpis,
        .product-kpis,
        .crm-grid,
        .sales-summary-grid,
        .sales-report-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
