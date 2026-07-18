# TEMS Phase 0 Stabilization

## Purpose

Phase 0 turns the current repository into a stable baseline before more enterprise modules are added. It preserves working functionality, maps what exists, fixes critical runtime risks, and records the order of implementation from the master prompt.

## Current Architecture

TEMS is a Laravel application with two active layers:

- Native Laravel modules: Commercial Operations, Planning and Performance, Finance foundation, HR organization foundation, Users and Roles, Security settings, Audit logs, and shared navigation.
- Legacy ERP screens: Sales, POS, Customers, CRM, Products, Inventory, Purchases, Suppliers, Payroll, HR employee operations, Reports, Settings, and Document Templates.

The native modules use controllers, Eloquent models, service classes, migrations, seeders, Blade views, and feature tests. The legacy modules are still routed through `LegacyPageController` and wrapped inside the Laravel layout.

## Existing Native Modules

| Module | Evidence | Phase 0 status |
| --- | --- | --- |
| Commercial Operations | `app/Models/Commercial`, `app/Services/Commercial`, `resources/views/commercial` | Strong Phase 1 foundation |
| Planning and Performance | `app/Models/Planning`, `app/Services/Planning`, `resources/views/planning` | Phase 1 foundation |
| Finance | `app/Models/Finance`, `app/Services/Finance`, `resources/views/finance` | Control foundation |
| HR Organization | `app/Models/HR`, `resources/views/hr` | Departments and positions |
| Users, Roles and Security | `app/Models/Role.php`, `app/Support/PermissionCatalog.php`, settings views | Partial enterprise foundation |
| Navigation | `app/Support/Navigation.php` | Single sidebar builder |

## Existing Legacy Modules

Sales, POS, Customers, CRM, Products, Inventory, Purchases, Suppliers, HR employee screens, Payroll, Reports, Notifications, Mobile App, Document Templates, Accounting, Banking, Budgets, Assets, and Settings still exist as legacy views under `resources/views/legacy`.

## Duplicate or Transitional Entities

| Business concept | Current native entity | Current legacy/shared entity | Phase 0 decision |
| --- | --- | --- | --- |
| Organization/customer | `commercial_organizations` | `customers` | Keep both; Commercial tracks professional organization records and links to customers when revenue work begins |
| Opportunity/sale | `commercial_opportunities` | `invoices` quotations/invoices | Keep bridge through `commercial_sales_handoffs` |
| HR structure/employee | `hr_departments`, `hr_positions` | `hr_employees`, HR legacy screens | Keep structure native; migrate employee lifecycle later |
| Finance transaction/source documents | `finance_transactions` | invoices, purchases, payroll, inventory transactions | Finance sync remains source-based until GL phase |
| Planning targets/module execution | planning tables | legacy sales/finance/inventory/HR data | Keep planning as performance layer; integrate via events later |

## Critical Phase 0 Fix Applied

The shared legacy business tables for customers, products, invoices, invoice payments, suppliers, purchases, purchase payments, and inventory transactions now have a canonical migration:

`database/migrations/2026_07_18_000600_create_legacy_business_core_tables.php`

This reduces the previous risk where Sales, POS, Commercial handoff, Finance sync, and Purchasing could depend on tables created dynamically during page execution.

## Remaining Risks

- Legacy views still contain table/column guard code. These guards should be retired gradually after native replacements or after the legacy SQL is fully mapped.
- Many legacy routes accept both GET and POST through the same controller.
- Several large business domains are still legacy-first: Sales, CRM, Inventory, Procurement, Payroll, Reports, and Employee lifecycle.
- Domain events, approvals, notifications, and intelligence are not yet system-wide foundations.
- The repository contains many untracked and modified files. This should be committed or otherwise checkpointed before Phase 1.

## Confirmed Implementation Order

1. Phase 0: stabilization, documentation, critical schema risks, auth/permission/migration confirmation.
2. Phase 1: enterprise foundation, company configuration, roles, permissions, approvals, audit, notifications, events, shared UI standards.
3. Phase 2: strategy, planning, performance, evidence, reviews, corrective action.
4. Phase 3: growth and revenue, including Marketing, Commercial, CRM, Customer Accounts, Sales.
5. Phase 4: finance and procurement depth.
6. Phase 5: products, engineering, projects, and delivery.
7. Phase 6: customer success, legal, governance, knowledge.
8. Phase 7: intelligence engine and decision support.

## Phase 0 Definition of Done

- Master prompt exists in the repository.
- Current modules are mapped.
- Critical business tables are migration-backed.
- Commercial-to-Sales handoff no longer creates sales tables at runtime.
- Tests confirm the migrated business core and existing module flows.
