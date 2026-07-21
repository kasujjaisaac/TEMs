<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_profiles')) {
            Schema::create('employee_profiles', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('position_id')->nullable()->index();
                $table->string('employee_number', 60);
                $table->string('full_name');
                $table->string('work_email')->nullable();
                $table->string('employment_status', 60)->default('Active')->index();
                $table->date('joined_on')->nullable();
                $table->unsignedBigInteger('supervisor_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'employee_number']);
            });
        }

        if (! Schema::hasTable('approval_rules')) {
            Schema::create('approval_rules', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('module', 80)->index();
                $table->string('request_type', 120)->index();
                $table->decimal('minimum_amount', 15, 2)->default(0);
                $table->string('approver_role', 120)->nullable();
                $table->unsignedBigInteger('approver_user_id')->nullable()->index();
                $table->unsignedSmallInteger('sequence')->default(1);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('document_number_sequences')) {
            Schema::create('document_number_sequences', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('document_type', 80);
                $table->string('prefix', 20);
                $table->unsignedSmallInteger('year');
                $table->unsignedInteger('next_number')->default(1);
                $table->timestamps();
                $table->unique(['tenant_id', 'document_type', 'year']);
            });
        }

        if (! Schema::hasTable('finance_expenses')) {
            Schema::create('finance_expenses', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('budget_line_id')->nullable()->index();
                $table->string('reference', 60);
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->string('currency', 8)->default('UGX');
                $table->date('expense_date');
                $table->string('status', 60)->default('Submitted')->index();
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('purchase_requests')) {
            Schema::create('purchase_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('budget_line_id')->nullable()->index();
                $table->string('reference', 60);
                $table->string('title');
                $table->text('justification')->nullable();
                $table->decimal('estimated_amount', 15, 2)->default(0);
                $table->string('status', 60)->default('Draft')->index();
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('purchase_request_id')->nullable()->index();
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->string('reference', 60);
                $table->string('supplier_name')->nullable();
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('status', 60)->default('Issued')->index();
                $table->date('issued_on')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('supplier_bills')) {
            Schema::create('supplier_bills', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
                $table->string('reference', 60);
                $table->string('supplier_name');
                $table->decimal('amount', 15, 2);
                $table->date('bill_date');
                $table->date('due_date')->nullable();
                $table->string('status', 60)->default('Unpaid')->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('finance_payments')) {
            Schema::create('finance_payments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('supplier_bill_id')->nullable()->index();
                $table->string('reference', 60);
                $table->string('payee_name');
                $table->decimal('amount', 15, 2);
                $table->date('payment_date');
                $table->string('method', 80)->nullable();
                $table->string('status', 60)->default('Paid')->index();
                $table->unsignedBigInteger('paid_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('asset_register')) {
            Schema::create('asset_register', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('reference', 60);
                $table->string('name');
                $table->string('category', 120)->nullable();
                $table->decimal('cost', 15, 2)->default(0);
                $table->date('acquired_on')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->string('status', 60)->default('In Use')->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_register');
        Schema::dropIfExists('finance_payments');
        Schema::dropIfExists('supplier_bills');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('finance_expenses');
        Schema::dropIfExists('document_number_sequences');
        Schema::dropIfExists('approval_rules');
        Schema::dropIfExists('employee_profiles');
    }
};
