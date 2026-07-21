<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enterprise_workflow_checks')) {
            Schema::create('enterprise_workflow_checks', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('module', 120)->index();
                $table->string('workflow', 120)->index();
                $table->string('source_type', 120);
                $table->unsignedBigInteger('source_id');
                $table->string('control_key', 120);
                $table->string('control_label');
                $table->string('status', 40)->default('Pending')->index();
                $table->unsignedBigInteger('checked_by')->nullable()->index();
                $table->timestamp('checked_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'workflow', 'source_type', 'source_id', 'control_key'], 'enterprise_workflow_check_unique');
                $table->index(['source_type', 'source_id'], 'enterprise_workflow_check_source_idx');
            });
        }

        if (! Schema::hasTable('finance_control_reviews')) {
            Schema::create('finance_control_reviews', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('source_module', 120)->index();
                $table->string('source_type', 120)->index();
                $table->unsignedBigInteger('source_id')->index();
                $table->string('decision', 60)->index();
                $table->string('status', 60)->default('Recorded')->index();
                $table->decimal('amount', 15, 2)->nullable();
                $table->string('currency', 8)->default('UGX');
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('project_delivery_gates')) {
            Schema::create('project_delivery_gates', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_id')->index();
                $table->unsignedBigInteger('milestone_id')->nullable()->index();
                $table->string('gate_type', 120)->index();
                $table->string('title');
                $table->string('status', 60)->default('Pending')->index();
                $table->unsignedBigInteger('verified_by')->nullable()->index();
                $table->timestamp('verified_at')->nullable();
                $table->text('evidence_summary')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_success_handovers')) {
            Schema::create('customer_success_handovers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('status', 60)->default('Pending')->index();
                $table->string('onboarding_status', 80)->default('Ready')->index();
                $table->unsignedTinyInteger('health_score')->default(70)->index();
                $table->string('risk_level', 40)->default('Low')->index();
                $table->text('handover_notes')->nullable();
                $table->timestamp('handover_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('enterprise_generated_documents')) {
            Schema::create('enterprise_generated_documents', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('document_record_id')->nullable()->index();
                $table->string('module', 120)->index();
                $table->string('document_type', 120)->index();
                $table->string('source_type', 120)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('reference', 120);
                $table->string('title');
                $table->string('status', 60)->default('Generated')->index();
                $table->unsignedBigInteger('generated_by')->nullable()->index();
                $table->timestamp('generated_at')->nullable();
                $table->longText('content')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'enterprise_documents_reference_unique');
                $table->index(['source_type', 'source_id'], 'enterprise_documents_source_idx');
            });
        }

        if (! Schema::hasTable('enterprise_scorecard_snapshots')) {
            Schema::create('enterprise_scorecard_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('scope', 80)->default('Company')->index();
                $table->unsignedBigInteger('scope_id')->nullable()->index();
                $table->date('scorecard_date')->index();
                $table->unsignedTinyInteger('health_score')->default(50)->index();
                $table->decimal('revenue_amount', 15, 2)->default(0);
                $table->decimal('pipeline_amount', 15, 2)->default(0);
                $table->decimal('budget_utilization', 8, 2)->default(0);
                $table->decimal('project_completion', 8, 2)->default(0);
                $table->unsignedTinyInteger('customer_health')->default(50);
                $table->unsignedInteger('verified_evidence_count')->default(0);
                $table->unsignedInteger('risk_count')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'scope', 'scope_id', 'scorecard_date'], 'enterprise_scorecard_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_scorecard_snapshots');
        Schema::dropIfExists('enterprise_generated_documents');
        Schema::dropIfExists('customer_success_handovers');
        Schema::dropIfExists('project_delivery_gates');
        Schema::dropIfExists('finance_control_reviews');
        Schema::dropIfExists('enterprise_workflow_checks');
    }
};
