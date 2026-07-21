<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('approval_requests', 'current_step')) {
            Schema::table('approval_requests', function (Blueprint $table): void {
                $table->unsignedSmallInteger('current_step')->default(1)->after('priority');
                $table->unsignedBigInteger('current_approver_id')->nullable()->index()->after('current_step');
                $table->decimal('amount', 15, 2)->nullable()->after('summary');
            });
        }

        if (! Schema::hasTable('approval_steps')) {
            Schema::create('approval_steps', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('approval_request_id')->index();
                $table->unsignedSmallInteger('sequence')->default(1)->index();
                $table->string('approver_role', 120)->nullable();
                $table->unsignedBigInteger('approver_user_id')->nullable()->index();
                $table->string('status', 40)->default('Pending')->index();
                $table->unsignedBigInteger('decided_by')->nullable()->index();
                $table->timestamp('decided_at')->nullable();
                $table->text('decision_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['approval_request_id', 'sequence']);
            });
        }

        if (! Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('source_module', 80)->default('*')->index();
                $table->string('type', 80)->default('*')->index();
                $table->boolean('in_app_enabled')->default(true);
                $table->boolean('email_enabled')->default(false);
                $table->boolean('sms_enabled')->default(false);
                $table->timestamps();
                $table->unique(['tenant_id', 'user_id', 'source_module', 'type'], 'notification_pref_unique');
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                if (! Schema::hasColumn('customers', 'enterprise_identity_status')) {
                    $table->string('enterprise_identity_status', 60)->default('Canonical')->index()->after('commercial_sync_status');
                }
                if (! Schema::hasColumn('customers', 'source_of_truth')) {
                    $table->string('source_of_truth', 80)->default('CRM Customer Account')->index()->after('enterprise_identity_status');
                }
            });
        }

        if (! Schema::hasTable('customer_identity_links')) {
            Schema::create('customer_identity_links', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('source_table', 120)->index();
                $table->unsignedBigInteger('source_id')->index();
                $table->string('source_reference', 120)->nullable()->index();
                $table->string('link_type', 80)->default('canonical');
                $table->string('match_method', 80)->default('explicit');
                $table->unsignedTinyInteger('confidence')->default(100);
                $table->string('status', 40)->default('Active')->index();
                $table->timestamp('linked_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['tenant_id', 'source_table', 'source_id'], 'customer_identity_source_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_identity_links');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('approval_steps');
    }
};
