<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workplan_evidence')) {
            Schema::create('workplan_evidence', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('workplan_item_id')->index();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->string('title');
                $table->string('evidence_type', 120);
                $table->text('description')->nullable();
                $table->string('source_module', 120)->nullable()->index();
                $table->string('source_reference', 120)->nullable();
                $table->decimal('claimed_value', 15, 2)->default(0);
                $table->decimal('verified_value', 15, 2)->default(0);
                $table->string('status', 40)->default('Submitted')->index();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('workplan_evidence_reviews')) {
            Schema::create('workplan_evidence_reviews', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('workplan_evidence_id')->index();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->string('decision', 40)->index();
                $table->decimal('verified_value', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('workplan_corrective_actions')) {
            Schema::create('workplan_corrective_actions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('workplan_item_id')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->string('title');
                $table->text('root_cause')->nullable();
                $table->text('recovery_plan');
                $table->date('due_on')->nullable()->index();
                $table->string('status', 40)->default('Open')->index();
                $table->string('severity', 40)->default('Medium')->index();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workplan_corrective_actions');
        Schema::dropIfExists('workplan_evidence_reviews');
        Schema::dropIfExists('workplan_evidence');
    }
};
