<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_fiscal_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 80);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 40)->default('Open');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->unique(['tenant_id', 'name'], 'finance_fiscal_year_unique');
        });

        Schema::create('finance_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('fiscal_year_id')->constrained('finance_fiscal_years')->cascadeOnDelete();
            $table->string('name', 40);
            $table->unsignedTinyInteger('period_number');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 40)->default('Open');
            $table->timestamps();
            $table->unique(['tenant_id', 'fiscal_year_id', 'period_number'], 'finance_period_unique');
        });

        Schema::create('finance_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('parent_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('type', 60);
            $table->string('normal_balance', 20);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_cash_account')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code'], 'finance_account_code_unique');
        });

        Schema::create('finance_cost_centres', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('type', 80)->default('Department');
            $table->string('owner_name', 160)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code'], 'finance_cost_centre_code_unique');
        });

        Schema::create('finance_budget_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('fiscal_year_id')->constrained('finance_fiscal_years')->cascadeOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('finance_periods')->nullOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->constrained('finance_cost_centres')->nullOnDelete();
            $table->string('reference', 60);
            $table->string('description', 220);
            $table->string('workplan_objective', 220)->nullable();
            $table->decimal('annual_budget', 15, 2)->default(0);
            $table->decimal('monthly_allocation', 15, 2)->default(0);
            $table->decimal('committed_amount', 15, 2)->default(0);
            $table->decimal('actual_spent', 15, 2)->default(0);
            $table->decimal('forecast_amount', 15, 2)->default(0);
            $table->string('owner_name', 160)->nullable();
            $table->string('approver_name', 160)->nullable();
            $table->string('status', 40)->default('Draft');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'reference'], 'finance_budget_reference_unique');
        });

        Schema::create('finance_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('fiscal_year_id')->nullable()->constrained('finance_fiscal_years')->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('finance_periods')->nullOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('budget_line_id')->nullable()->constrained('finance_budget_lines')->nullOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->constrained('finance_cost_centres')->nullOnDelete();
            $table->string('reference', 100);
            $table->string('source_module', 80);
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('counterparty_type', 80)->nullable();
            $table->unsignedBigInteger('counterparty_id')->nullable();
            $table->string('counterparty_name', 180)->nullable();
            $table->string('direction', 20);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 8)->default('UGX');
            $table->date('transaction_date');
            $table->date('due_date')->nullable();
            $table->string('status', 80)->default('Draft');
            $table->string('approval_status', 80)->default('Not Required');
            $table->string('evidence_status', 80)->default('Pending');
            $table->text('description')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['tenant_id', 'source_module', 'source_type', 'source_id'], 'finance_source_unique');
            $table->index(['tenant_id', 'transaction_date'], 'finance_transaction_date_idx');
        });

        Schema::create('finance_journals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('period_id')->nullable()->constrained('finance_periods')->nullOnDelete();
            $table->unsignedBigInteger('finance_transaction_id')->nullable()->index();
            $table->string('reference', 100);
            $table->date('journal_date');
            $table->string('status', 40)->default('Draft');
            $table->text('memo')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('posted_by')->nullable()->index();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'reference'], 'finance_journal_reference_unique');
        });

        Schema::create('finance_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('journal_id')->constrained('finance_journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->constrained('finance_cost_centres')->nullOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_alerts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('severity', 40)->default('Info');
            $table->string('category', 80);
            $table->string('title', 180);
            $table->text('message');
            $table->string('status', 40)->default('Open');
            $table->string('source_module', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_alerts');
        Schema::dropIfExists('finance_journal_lines');
        Schema::dropIfExists('finance_journals');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_budget_lines');
        Schema::dropIfExists('finance_cost_centres');
        Schema::dropIfExists('finance_accounts');
        Schema::dropIfExists('finance_periods');
        Schema::dropIfExists('finance_fiscal_years');
    }
};
