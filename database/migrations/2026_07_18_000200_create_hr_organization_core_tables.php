<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('short_name', 80)->nullable();
            $table->string('type', 40)->default('Department');
            $table->text('description')->nullable();
            $table->text('mandate')->nullable();
            $table->text('responsibilities')->nullable();
            $table->string('cost_centre', 80)->nullable();
            $table->unsignedBigInteger('head_position_id')->nullable()->index();
            $table->string('status', 60)->default('Proposed');
            $table->date('effective_from')->nullable();
            $table->date('review_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'hr_departments_code_unique');
            $table->index(['tenant_id', 'status'], 'hr_departments_status_idx');
        });

        Schema::create('hr_positions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('department_id')->constrained('hr_departments')->cascadeOnDelete();
            $table->unsignedBigInteger('reports_to_position_id')->nullable()->index();
            $table->string('code', 40);
            $table->string('title', 160);
            $table->string('job_family', 100)->nullable();
            $table->string('grade', 80)->nullable();
            $table->string('level', 80)->nullable();
            $table->string('employment_type', 80)->default('Full time');
            $table->string('work_location', 120)->nullable();
            $table->text('job_purpose')->nullable();
            $table->text('key_responsibilities')->nullable();
            $table->text('standard_kpis')->nullable();
            $table->text('competencies')->nullable();
            $table->text('decision_rights')->nullable();
            $table->decimal('approval_limit', 15, 2)->nullable();
            $table->unsignedSmallInteger('approved_headcount')->default(1);
            $table->unsignedSmallInteger('filled_headcount')->default(0);
            $table->string('position_status', 60)->default('Planned');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'hr_positions_code_unique');
            $table->index(['tenant_id', 'position_status'], 'hr_positions_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_positions');
        Schema::dropIfExists('hr_departments');
    }
};
