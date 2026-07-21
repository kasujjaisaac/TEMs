<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('executive_directives')) {
            Schema::create('executive_directives', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('reference', 80);
                $table->string('title');
                $table->text('directive')->nullable();
                $table->string('priority', 40)->default('Normal');
                $table->date('due_on')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('status', 60)->default('Open');
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'executive_directives_reference_unique');
                $table->index(['tenant_id', 'status'], 'executive_directives_status_idx');
            });
        }

        if (! Schema::hasTable('executive_decisions')) {
            Schema::create('executive_decisions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('reference', 80);
                $table->string('title');
                $table->text('decision')->nullable();
                $table->string('decision_type', 80)->nullable();
                $table->date('decided_on')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('status', 60)->default('Active');
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'executive_decisions_reference_unique');
            });
        }

        if (! Schema::hasTable('corporate_risks')) {
            Schema::create('corporate_risks', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('reference', 80);
                $table->string('title');
                $table->string('category', 120)->nullable();
                $table->string('risk_level', 40)->default('Medium');
                $table->text('mitigation')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->date('review_due_on')->nullable();
                $table->string('status', 60)->default('Open');
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'corporate_risks_reference_unique');
                $table->index(['tenant_id', 'risk_level', 'status'], 'corporate_risks_level_idx');
            });
        }

        if (! Schema::hasTable('marketing_communication_plans')) {
            Schema::create('marketing_communication_plans', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('reference', 80);
                $table->string('title');
                $table->string('channel', 80)->nullable();
                $table->string('audience', 160)->nullable();
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->decimal('budget', 15, 2)->default(0);
                $table->string('status', 60)->default('Planned');
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'marketing_plans_reference_unique');
            });
        }

        if (! Schema::hasTable('marketing_content_items')) {
            Schema::create('marketing_content_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('plan_id')->nullable()->index();
                $table->string('reference', 80);
                $table->string('title');
                $table->string('content_type', 80)->nullable();
                $table->string('approval_status', 60)->default('Draft');
                $table->date('publish_on')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'marketing_content_reference_unique');
            });
        }

        if (! Schema::hasTable('engineering_backlog_items')) {
            Schema::create('engineering_backlog_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('reference', 80);
                $table->string('title');
                $table->string('item_type', 80)->default('Feature');
                $table->string('priority', 40)->default('Medium');
                $table->string('release_target', 120)->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('status', 60)->default('Backlog');
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'engineering_backlog_reference_unique');
                $table->index(['tenant_id', 'status'], 'engineering_backlog_status_idx');
            });
        }

        if (! Schema::hasTable('engineering_quality_defects')) {
            Schema::create('engineering_quality_defects', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('reference', 80);
                $table->string('title');
                $table->string('severity', 40)->default('Medium');
                $table->string('environment', 80)->nullable();
                $table->text('resolution_notes')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('status', 60)->default('Open');
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'engineering_defects_reference_unique');
            });
        }

        if (! Schema::hasTable('knowledge_articles')) {
            Schema::create('knowledge_articles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('reference', 80);
                $table->string('title');
                $table->string('category', 120)->nullable();
                $table->text('summary')->nullable();
                $table->string('review_status', 60)->default('Draft');
                $table->date('review_due_on')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'knowledge_articles_reference_unique');
                $table->index(['tenant_id', 'review_status'], 'knowledge_articles_review_idx');
            });
        }

        if (! Schema::hasTable('report_definitions')) {
            Schema::create('report_definitions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('reference', 80);
                $table->string('name');
                $table->string('module', 120);
                $table->string('frequency', 60)->default('Monthly');
                $table->string('visibility', 60)->default('Management');
                $table->json('metrics')->nullable();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->string('status', 60)->default('Active');
                $table->timestamps();
                $table->unique(['tenant_id', 'reference'], 'report_definitions_reference_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('report_definitions');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('engineering_quality_defects');
        Schema::dropIfExists('engineering_backlog_items');
        Schema::dropIfExists('marketing_content_items');
        Schema::dropIfExists('marketing_communication_plans');
        Schema::dropIfExists('corporate_risks');
        Schema::dropIfExists('executive_decisions');
        Schema::dropIfExists('executive_directives');
    }
};
