<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('planning_daily_tasks')) {
            Schema::create('planning_daily_tasks', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('workplan_item_id')->nullable()->index();
                $table->unsignedBigInteger('target_allocation_id')->nullable()->index();
                $table->unsignedBigInteger('corrective_action_id')->nullable()->index();
                $table->unsignedBigInteger('workplan_evidence_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->nullable()->index();
                $table->unsignedBigInteger('position_id')->nullable()->index();
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->unsignedBigInteger('employee_profile_id')->nullable()->index();
                $table->unsignedBigInteger('supervisor_id')->nullable()->index();
                $table->string('source_module', 120)->nullable()->index();
                $table->string('source_type', 120)->nullable()->index();
                $table->unsignedBigInteger('source_id')->nullable()->index();
                $table->string('source_reference', 160)->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('expected_output')->nullable();
                $table->string('priority', 40)->default('Medium')->index();
                $table->date('task_date')->index();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('due_at')->nullable()->index();
                $table->string('status', 40)->default('Not Started')->index();
                $table->unsignedTinyInteger('progress_percent')->default(0);
                $table->string('evidence_status', 40)->default('Not Required')->index();
                $table->decimal('claimed_value', 15, 2)->default(0);
                $table->string('blocker_summary')->nullable();
                $table->text('completion_notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('review_decision', 40)->nullable()->index();
                $table->text('review_notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['tenant_id', 'employee_id', 'task_date'], 'daily_tasks_employee_day_idx');
                $table->index(['tenant_id', 'supervisor_id', 'status'], 'daily_tasks_supervisor_status_idx');
                $table->unique(['tenant_id', 'source_type', 'source_id', 'employee_id', 'task_date'], 'daily_tasks_source_employee_day_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_daily_tasks');
    }
};
