<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_account_plans')) {
            Schema::create('crm_account_plans', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('commercial_organization_id')->nullable()->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('relationship_stage', 80)->default('Active')->index();
                $table->text('objectives')->nullable();
                $table->text('growth_strategy')->nullable();
                $table->text('retention_strategy')->nullable();
                $table->string('health_status', 60)->default('Stable')->index();
                $table->string('risk_level', 40)->default('Medium')->index();
                $table->date('next_review_on')->nullable()->index();
                $table->string('status', 60)->default('Active')->index();
                $table->timestamps();
                $table->unique(['tenant_id', 'customer_id'], 'crm_account_plan_customer_unique');
            });
        }

        if (! Schema::hasTable('crm_account_timeline')) {
            Schema::create('crm_account_timeline', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('commercial_organization_id')->nullable()->index();
                $table->string('event_type', 100)->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('source_module', 120)->index();
                $table->string('source_type', 120)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->timestamp('occurred_at')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['source_type', 'source_id'], 'crm_account_timeline_source_idx');
            });
        }

        if (! Schema::hasTable('crm_customer_health_snapshots')) {
            Schema::create('crm_customer_health_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('commercial_organization_id')->nullable()->index();
                $table->unsignedTinyInteger('health_score')->default(50)->index();
                $table->string('health_status', 60)->default('Stable')->index();
                $table->string('risk_level', 40)->default('Medium')->index();
                $table->decimal('open_pipeline_value', 15, 2)->default(0);
                $table->decimal('lifetime_revenue', 15, 2)->default(0);
                $table->unsignedInteger('open_ticket_count')->default(0);
                $table->unsignedInteger('active_opportunity_count')->default(0);
                $table->date('snapshot_date')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'customer_id', 'snapshot_date'], 'crm_health_customer_date_unique');
            });
        }

        if (! Schema::hasTable('commercial_stage_controls')) {
            Schema::create('commercial_stage_controls', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->string('stage', 100)->index();
                $table->string('control_key', 120);
                $table->string('control_label');
                $table->string('status', 40)->default('Pending')->index();
                $table->unsignedBigInteger('verified_by')->nullable()->index();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'opportunity_id', 'stage', 'control_key'], 'commercial_stage_control_unique');
            });
        }

        if (! Schema::hasTable('commercial_negotiations')) {
            Schema::create('commercial_negotiations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->unsignedBigInteger('stakeholder_id')->nullable()->index();
                $table->string('topic', 160);
                $table->text('customer_position')->nullable();
                $table->text('texaro_position')->nullable();
                $table->decimal('proposed_value', 15, 2)->nullable();
                $table->decimal('agreed_value', 15, 2)->nullable();
                $table->string('status', 60)->default('Open')->index();
                $table->date('next_follow_up_on')->nullable()->index();
                $table->unsignedBigInteger('recorded_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commercial_renewals')) {
            Schema::create('commercial_renewals', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('contract_id')->nullable()->index();
                $table->string('reference', 80);
                $table->date('renewal_due_on')->index();
                $table->decimal('renewal_value', 15, 2)->default(0);
                $table->string('currency', 8)->default('UGX');
                $table->string('status', 60)->default('Due')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->text('retention_plan')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'commercial_renewal_reference_unique');
            });
        }

        if (! Schema::hasTable('commercial_expansion_opportunities')) {
            Schema::create('commercial_expansion_opportunities', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('source_opportunity_id')->nullable()->index();
                $table->string('reference', 80);
                $table->string('expansion_type', 80)->default('Upsell')->index();
                $table->string('title');
                $table->decimal('estimated_value', 15, 2)->default(0);
                $table->string('currency', 8)->default('UGX');
                $table->string('status', 60)->default('Identified')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->text('rationale')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'commercial_expansion_reference_unique');
            });
        }

        if (! Schema::hasTable('commercial_lost_opportunity_analyses')) {
            Schema::create('commercial_lost_opportunity_analyses', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->string('primary_reason', 160);
                $table->string('competitor_name', 160)->nullable();
                $table->text('lessons_learned')->nullable();
                $table->text('recovery_action')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable()->index();
                $table->timestamp('recorded_at')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'opportunity_id'], 'commercial_lost_analysis_opportunity_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_lost_opportunity_analyses');
        Schema::dropIfExists('commercial_expansion_opportunities');
        Schema::dropIfExists('commercial_renewals');
        Schema::dropIfExists('commercial_negotiations');
        Schema::dropIfExists('commercial_stage_controls');
        Schema::dropIfExists('crm_customer_health_snapshots');
        Schema::dropIfExists('crm_account_timeline');
        Schema::dropIfExists('crm_account_plans');
    }
};
