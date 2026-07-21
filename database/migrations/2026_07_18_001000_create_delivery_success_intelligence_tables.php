<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products_portfolio')) {
            Schema::create('products_portfolio', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('reference', 40);
                $table->string('name');
                $table->string('category', 120)->nullable();
                $table->string('lifecycle_stage', 80)->default('Idea')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->decimal('target_revenue', 15, 2)->default(0);
                $table->unsignedTinyInteger('health_score')->default(50);
                $table->string('status', 40)->default('Active')->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('engineering_releases')) {
            Schema::create('engineering_releases', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('reference', 40);
                $table->string('version');
                $table->string('environment', 80)->default('Production')->index();
                $table->date('planned_release_date')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->string('status', 40)->default('Planned')->index();
                $table->text('release_notes')->nullable();
                $table->unsignedBigInteger('released_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('implementation_projects')) {
            Schema::create('implementation_projects', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('opportunity_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('reference', 40);
                $table->string('name');
                $table->text('scope')->nullable();
                $table->unsignedBigInteger('project_manager_id')->nullable()->index();
                $table->date('starts_on')->nullable();
                $table->date('due_on')->nullable();
                $table->unsignedTinyInteger('progress')->default(0);
                $table->decimal('budget', 15, 2)->default(0);
                $table->decimal('actual_cost', 15, 2)->default(0);
                $table->string('health_status', 40)->default('Not Started')->index();
                $table->string('status', 40)->default('Initiated')->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('project_milestones')) {
            Schema::create('project_milestones', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_id')->index();
                $table->string('title');
                $table->date('due_on')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('acceptance_status', 40)->default('Pending')->index();
                $table->string('status', 40)->default('Open')->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('customer_success_accounts')) {
            Schema::create('customer_success_accounts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('onboarding_status', 80)->default('Not Started')->index();
                $table->unsignedTinyInteger('health_score')->default(50)->index();
                $table->string('risk_level', 40)->default('Medium')->index();
                $table->date('renewal_date')->nullable()->index();
                $table->text('success_plan')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('organization_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('reference', 40);
                $table->string('subject');
                $table->text('description')->nullable();
                $table->string('priority', 40)->default('Medium')->index();
                $table->string('status', 40)->default('Open')->index();
                $table->timestamp('sla_due_at')->nullable()->index();
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('compliance_obligations')) {
            Schema::create('compliance_obligations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('reference', 40);
                $table->string('title');
                $table->string('category', 120)->nullable();
                $table->date('due_on')->nullable()->index();
                $table->string('risk_level', 40)->default('Medium')->index();
                $table->string('status', 40)->default('Open')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('board_governance_actions')) {
            Schema::create('board_governance_actions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('reference', 40);
                $table->string('title');
                $table->string('source_meeting')->nullable();
                $table->date('due_on')->nullable()->index();
                $table->string('status', 40)->default('Open')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('intelligence_metric_snapshots')) {
            Schema::create('intelligence_metric_snapshots', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('metric_key', 120)->index();
                $table->string('metric_name');
                $table->decimal('metric_value', 18, 2)->default(0);
                $table->string('unit', 40)->nullable();
                $table->string('source_module', 120)->index();
                $table->timestamp('captured_at')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('intelligence_signals')) {
            Schema::create('intelligence_signals', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('signal_type', 80)->index();
                $table->string('severity', 40)->default('Info')->index();
                $table->string('title');
                $table->text('message');
                $table->string('source_module', 120)->index();
                $table->string('status', 40)->default('Open')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('intelligence_recommendations')) {
            Schema::create('intelligence_recommendations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('priority', 40)->default('Medium')->index();
                $table->string('title');
                $table->text('recommendation');
                $table->string('source_module', 120)->index();
                $table->string('status', 40)->default('Open')->index();
                $table->unsignedBigInteger('assigned_to')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('intelligence_recommendations');
        Schema::dropIfExists('intelligence_signals');
        Schema::dropIfExists('intelligence_metric_snapshots');
        Schema::dropIfExists('board_governance_actions');
        Schema::dropIfExists('compliance_obligations');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('customer_success_accounts');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('implementation_projects');
        Schema::dropIfExists('engineering_releases');
        Schema::dropIfExists('products_portfolio');
    }
};
