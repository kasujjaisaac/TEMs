<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                if (! Schema::hasColumn('customers', 'commercial_organization_id')) {
                    $table->unsignedBigInteger('commercial_organization_id')->nullable()->index()->after('tenant_id');
                }
                if (! Schema::hasColumn('customers', 'commercial_reference')) {
                    $table->string('commercial_reference', 80)->nullable()->index()->after('customer_code');
                }
                if (! Schema::hasColumn('customers', 'commercial_sync_status')) {
                    $table->string('commercial_sync_status', 60)->nullable()->after('customer_source');
                }
                if (! Schema::hasColumn('customers', 'commercial_synced_at')) {
                    $table->timestamp('commercial_synced_at')->nullable()->after('commercial_sync_status');
                }
            });
        }

        if (! Schema::hasTable('crm_leads')) {
            Schema::create('crm_leads', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('commercial_lead_id')->nullable()->index();
                $table->string('contact_name', 155);
                $table->string('company_name')->nullable();
                $table->string('phone', 80)->nullable();
                $table->string('email')->nullable();
                $table->string('source', 80)->default('commercial_operations');
                $table->string('status', 40)->default('new');
                $table->string('priority', 30)->default('normal');
                $table->string('assigned_to', 155)->nullable();
                $table->decimal('estimated_value', 15, 2)->default(0);
                $table->date('expected_close_date')->nullable();
                $table->unsignedBigInteger('converted_customer_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
            });
        } elseif (! Schema::hasColumn('crm_leads', 'commercial_lead_id')) {
            Schema::table('crm_leads', function (Blueprint $table): void {
                $table->unsignedBigInteger('commercial_lead_id')->nullable()->index()->after('customer_id');
            });
        }

        if (! Schema::hasTable('crm_opportunities')) {
            Schema::create('crm_opportunities', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('lead_id')->nullable()->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->unsignedBigInteger('commercial_opportunity_id')->nullable()->index();
                $table->string('title');
                $table->string('stage', 40)->default('qualification');
                $table->decimal('value', 15, 2)->default(0);
                $table->integer('probability')->default(25);
                $table->date('expected_close_date')->nullable();
                $table->string('owner', 155)->nullable();
                $table->string('status', 40)->default('open');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'stage']);
            });
        } elseif (! Schema::hasColumn('crm_opportunities', 'commercial_opportunity_id')) {
            Schema::table('crm_opportunities', function (Blueprint $table): void {
                $table->unsignedBigInteger('commercial_opportunity_id')->nullable()->index()->after('customer_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                foreach (['commercial_organization_id', 'commercial_reference', 'commercial_sync_status', 'commercial_synced_at'] as $column) {
                    if (Schema::hasColumn('customers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
