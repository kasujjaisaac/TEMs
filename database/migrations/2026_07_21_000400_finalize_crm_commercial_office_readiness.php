<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_customer_branches')) {
            Schema::create('crm_customer_branches', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('commercial_organization_id')->nullable()->index();
                $table->string('name');
                $table->string('branch_type', 80)->default('Branch')->index();
                $table->string('city', 120)->nullable();
                $table->string('country', 80)->nullable();
                $table->text('address')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 80)->nullable();
                $table->boolean('is_primary')->default(false)->index();
                $table->string('status', 60)->default('Active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crm_customer_documents')) {
            Schema::create('crm_customer_documents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('document_type', 120)->index();
                $table->string('title');
                $table->string('reference', 120)->nullable()->index();
                $table->string('status', 60)->default('Current')->index();
                $table->date('expires_on')->nullable()->index();
                $table->string('storage_path')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crm_customer_subscriptions')) {
            Schema::create('crm_customer_subscriptions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('commercial_organization_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('product_name');
                $table->string('plan_name')->nullable();
                $table->date('starts_on')->nullable();
                $table->date('renews_on')->nullable()->index();
                $table->decimal('recurring_amount', 15, 2)->default(0);
                $table->string('currency', 8)->default('UGX');
                $table->string('billing_frequency', 80)->nullable();
                $table->string('status', 60)->default('Active')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commercial_decision_process_maps')) {
            Schema::create('commercial_decision_process_maps', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->string('step_name');
                $table->unsignedSmallInteger('sequence')->default(1)->index();
                $table->unsignedBigInteger('stakeholder_id')->nullable()->index();
                $table->string('decision_role', 100)->nullable();
                $table->string('status', 60)->default('Pending')->index();
                $table->date('target_date')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commercial_quotation_items')) {
            Schema::create('commercial_quotation_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('quotation_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('description');
                $table->decimal('quantity', 12, 2)->default(1);
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('line_total', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commercial_generated_documents')) {
            Schema::create('commercial_generated_documents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->nullable()->index();
                $table->string('source_type', 120)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('document_type', 120)->index();
                $table->string('reference', 120);
                $table->string('title');
                $table->string('status', 60)->default('Generated')->index();
                $table->longText('content')->nullable();
                $table->unsignedBigInteger('generated_by')->nullable()->index();
                $table->timestamp('generated_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'commercial_generated_documents_reference_unique');
            });
        }

        if (! Schema::hasTable('commercial_reminders')) {
            Schema::create('commercial_reminders', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('source_type', 120);
                $table->unsignedBigInteger('source_id');
                $table->string('reminder_type', 100)->index();
                $table->string('title');
                $table->text('message')->nullable();
                $table->timestamp('due_at')->index();
                $table->string('status', 60)->default('Open')->index();
                $table->timestamps();
                $table->index(['source_type', 'source_id'], 'commercial_reminders_source_idx');
            });
        }

        if (! Schema::hasTable('commercial_report_snapshots')) {
            Schema::create('commercial_report_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->date('report_date')->index();
                $table->decimal('pipeline_value', 15, 2)->default(0);
                $table->decimal('weighted_pipeline_value', 15, 2)->default(0);
                $table->decimal('won_value', 15, 2)->default(0);
                $table->unsignedInteger('open_leads')->default(0);
                $table->unsignedInteger('open_opportunities')->default(0);
                $table->unsignedInteger('stale_opportunities')->default(0);
                $table->unsignedInteger('renewals_due')->default(0);
                $table->decimal('conversion_rate', 8, 2)->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'report_date'], 'commercial_report_snapshot_unique');
            });
        }

        if (! Schema::hasTable('crm_identity_conflict_reviews')) {
            Schema::create('crm_identity_conflict_reviews', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('commercial_organization_id')->nullable()->index();
                $table->string('conflict_type', 100)->index();
                $table->string('status', 60)->default('Open')->index();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_identity_conflict_reviews');
        Schema::dropIfExists('commercial_report_snapshots');
        Schema::dropIfExists('commercial_reminders');
        Schema::dropIfExists('commercial_generated_documents');
        Schema::dropIfExists('commercial_quotation_items');
        Schema::dropIfExists('commercial_decision_process_maps');
        Schema::dropIfExists('crm_customer_subscriptions');
        Schema::dropIfExists('crm_customer_documents');
        Schema::dropIfExists('crm_customer_branches');
    }
};
