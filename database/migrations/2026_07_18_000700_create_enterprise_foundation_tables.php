<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('group', 80)->default('company')->index();
            $table->string('key', 120);
            $table->text('value')->nullable();
            $table->string('value_type', 40)->default('string');
            $table->timestamps();
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('module', 80)->index();
            $table->string('request_type', 100)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('status', 40)->default('Pending')->index();
            $table->string('priority', 40)->default('Normal')->index();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('system_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('source_module', 80)->nullable()->index();
            $table->string('type', 80)->default('system')->index();
            $table->string('severity', 40)->default('Info')->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('domain_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('event_name', 120)->index();
            $table->string('source_module', 80)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->timestamp('occurred_at')->index();
            $table->string('status', 40)->default('Recorded')->index();
            $table->timestamp('processed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('document_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('module', 80)->index();
            $table->string('document_type', 100)->index();
            $table->string('reference', 120)->nullable();
            $table->string('title');
            $table->string('status', 40)->default('Draft')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->string('storage_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_records');
        Schema::dropIfExists('domain_events');
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('company_settings');
    }
};
