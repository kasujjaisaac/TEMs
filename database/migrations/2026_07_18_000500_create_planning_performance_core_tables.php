<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('planning_years')) {
            Schema::create('planning_years', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name', 80);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('annual_theme')->nullable();
            $table->string('status', 40)->default('Draft')->index();
            $table->boolean('is_current')->default(false)->index();
            $table->json('scoring_rules')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'name']);
            });
        }

        if (! Schema::hasTable('strategic_pillars')) {
            Schema::create('strategic_pillars', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('planning_year_id')->nullable()->index();
            $table->string('code', 40);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('owner_name')->nullable();
            $table->unsignedSmallInteger('weight')->default(0);
            $table->string('status', 40)->default('Active')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
            });
        }

        if (! Schema::hasTable('strategic_objectives')) {
            Schema::create('strategic_objectives', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('planning_year_id')->index();
            $table->unsignedBigInteger('strategic_pillar_id')->nullable()->index();
            $table->string('code', 40);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('kpi')->nullable();
            $table->decimal('baseline_value', 15, 2)->default(0);
            $table->decimal('target_value', 15, 2)->default(0);
            $table->string('unit', 80)->nullable();
            $table->unsignedSmallInteger('weight')->default(0);
            $table->string('owner_name')->nullable();
            $table->string('status', 40)->default('Draft')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
            });
        }

        if (! Schema::hasTable('workplans')) {
            Schema::create('workplans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('planning_year_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('position_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->string('code', 60);
            $table->string('title');
            $table->string('level', 40)->default('Corporate')->index();
            $table->text('description')->nullable();
            $table->string('owner_name')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->string('approval_status', 40)->default('Draft')->index();
            $table->string('health_status', 40)->default('Not Started')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
            });
        }

        if (! Schema::hasTable('workplan_items')) {
            Schema::create('workplan_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('workplan_id')->index();
            $table->unsignedBigInteger('strategic_objective_id')->nullable()->index();
            $table->unsignedBigInteger('budget_line_id')->nullable()->index();
            $table->string('reference', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('target_type', 40)->default('Numeric')->index();
            $table->string('kpi')->nullable();
            $table->decimal('baseline_value', 15, 2)->default(0);
            $table->decimal('target_value', 15, 2)->default(0);
            $table->decimal('actual_value', 15, 2)->default(0);
            $table->string('unit', 80)->nullable();
            $table->string('priority', 40)->default('Medium')->index();
            $table->unsignedSmallInteger('weight')->default(0);
            $table->date('starts_on')->nullable();
            $table->date('due_on')->nullable();
            $table->string('required_evidence_type')->nullable();
            $table->string('quality_standard')->nullable();
            $table->text('risk_summary')->nullable();
            $table->string('approval_status', 40)->default('Draft')->index();
            $table->string('health_status', 40)->default('Not Started')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'reference']);
            });
        }

        if (! Schema::hasTable('target_allocations')) {
            Schema::create('target_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('workplan_item_id')->index();
            $table->string('period_type', 20)->index();
            $table->date('period_start')->index();
            $table->date('period_end')->index();
            $table->decimal('target_value', 15, 2)->default(0);
            $table->decimal('actual_value', 15, 2)->default(0);
            $table->string('status', 40)->default('Planned')->index();
            $table->timestamps();
                $table->unique(['tenant_id', 'workplan_item_id', 'period_type', 'period_start'], 'ta_tenant_item_period_unique');
            });
        }

        if (! Schema::hasTable('workplan_assignments')) {
            Schema::create('workplan_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('workplan_item_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('position_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->unsignedBigInteger('supervisor_id')->nullable()->index();
            $table->string('assignment_role', 40)->default('Accountable')->index();
            $table->unsignedSmallInteger('contribution_weight')->default(100);
            $table->string('status', 40)->default('Active')->index();
            $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workplan_assignments');
        Schema::dropIfExists('target_allocations');
        Schema::dropIfExists('workplan_items');
        Schema::dropIfExists('workplans');
        Schema::dropIfExists('strategic_objectives');
        Schema::dropIfExists('strategic_pillars');
        Schema::dropIfExists('planning_years');
    }
};
