<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('customer_code', 80)->nullable();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('customer_type', 80)->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 80)->nullable();
                $table->text('address')->nullable();
                $table->text('billing_address')->nullable();
                $table->text('service_address')->nullable();
                $table->string('city', 120)->nullable();
                $table->string('country', 80)->nullable();
                $table->string('tin_number', 80)->nullable();
                $table->string('customer_group', 80)->nullable();
                $table->string('payment_terms', 120)->nullable();
                $table->string('preferred_payment_method', 80)->nullable();
                $table->string('credit_status', 80)->nullable();
                $table->string('account_manager', 155)->nullable();
                $table->string('customer_source', 120)->nullable();
                $table->text('internal_notes')->nullable();
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->decimal('credit_balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['tenant_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('sku', 100)->nullable();
                $table->string('barcode', 100)->nullable();
                $table->string('name');
                $table->string('type', 80)->default('product');
                $table->unsignedBigInteger('product_category_id')->nullable()->index();
                $table->unsignedBigInteger('income_category_id')->nullable()->index();
                $table->unsignedBigInteger('expense_category_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->decimal('selling_price', 15, 2)->default(0);
                $table->decimal('cost_price', 15, 2)->default(0);
                $table->decimal('vat_rate', 5, 2)->default(0);
                $table->integer('stock_quantity')->default(0);
                $table->integer('reorder_level')->default(0);
                $table->string('image_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['tenant_id', 'name']);
            });
        }

        foreach (['product_categories', 'income_categories', 'expense_categories'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->id();
                    $table->unsignedBigInteger('tenant_id')->index();
                    $table->string('name');
                    $table->string('code', 80)->nullable();
                    $table->text('description')->nullable();
                    $table->timestamps();
                    $table->unique(['tenant_id', 'name'], 'uq_' . $tableName . '_tenant_name');
                });
            }
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('invoice_number', 100);
                $table->string('invoice_type', 40)->default('invoice');
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->text('notes')->nullable();
                $table->string('terms', 80)->nullable();
                $table->string('salesperson', 155)->nullable();
                $table->string('branch_name', 155)->nullable();
                $table->string('customer_reference', 155)->nullable();
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('delivery_charge', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('status', 40)->default('draft');
                $table->unsignedBigInteger('source_invoice_id')->nullable()->index();
                $table->unsignedBigInteger('commercial_opportunity_id')->nullable()->index();
                $table->unsignedBigInteger('commercial_handoff_id')->nullable()->index();
                $table->boolean('stock_posted')->default(false);
                $table->boolean('accounting_posted')->default(false);
                $table->timestamps();
                $table->unique(['tenant_id', 'invoice_number']);
                $table->index(['tenant_id', 'invoice_date']);
                $table->index(['tenant_id', 'invoice_type', 'status']);
            });
        }

        if (! Schema::hasTable('invoice_lines')) {
            Schema::create('invoice_lines', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('invoice_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->integer('quantity')->default(1);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('line_total', 15, 2)->default(0);
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('invoice_id')->index();
                $table->date('payment_date');
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('method', 100)->nullable();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->string('received_by', 155)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('supplier_code', 80)->nullable();
                $table->string('company_name');
                $table->string('contact_person', 155)->nullable();
                $table->string('phone', 80)->nullable();
                $table->string('email', 155)->nullable();
                $table->text('address')->nullable();
                $table->string('tin_number', 100)->nullable();
                $table->string('payment_terms', 80)->nullable();
                $table->string('status', 40)->default('active');
                $table->decimal('credit_limit', 15, 2)->default(0);
                $table->decimal('credit_balance', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('purchase_number', 100)->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->string('supplier')->nullable();
                $table->string('invoice_number', 155)->nullable();
                $table->date('purchase_date')->nullable();
                $table->date('due_date')->nullable();
                $table->string('status', 40)->default('draft');
                $table->string('payment_status', 40)->default('unpaid');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax', 15, 2)->default(0);
                $table->decimal('discount', 15, 2)->default(0);
                $table->decimal('shipping', 15, 2)->default(0);
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->boolean('stock_posted')->default(false);
                $table->string('created_by', 155)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_lines')) {
            Schema::create('purchase_lines', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('purchase_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('line_total', 15, 2)->default(0);
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('purchase_payments')) {
            Schema::create('purchase_payments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('purchase_id')->index();
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->date('payment_date');
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('method', 80)->nullable();
                $table->string('reference', 155)->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('inventory_transactions')) {
            Schema::create('inventory_transactions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->string('transaction_type', 50);
                $table->integer('quantity')->default(0);
                $table->unsignedBigInteger('from_warehouse_id')->nullable()->index();
                $table->unsignedBigInteger('to_warehouse_id')->nullable()->index();
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Non-destructive: these tables are shared with imported legacy ERP screens.
    }
};
