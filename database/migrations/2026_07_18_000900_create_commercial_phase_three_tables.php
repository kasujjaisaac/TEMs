<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commercial_campaigns')) {
            Schema::create('commercial_campaigns', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('reference', 40);
                $table->string('name');
                $table->string('campaign_type', 80)->default('Demand Generation')->index();
                $table->string('channel', 80)->nullable()->index();
                $table->text('objective')->nullable();
                $table->string('target_audience')->nullable();
                $table->decimal('budget', 15, 2)->default(0);
                $table->decimal('actual_spend', 15, 2)->default(0);
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->string('status', 40)->default('Planned')->index();
                $table->unsignedBigInteger('owner_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'reference'], 'commercial_campaign_reference_unique');
            });
        }

        foreach (['commercial_leads', 'commercial_opportunities'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'campaign_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->unsignedBigInteger('campaign_id')->nullable()->index()->after('tenant_id');
                });
            }
        }

        if (! Schema::hasTable('commercial_proposals')) {
            Schema::create('commercial_proposals', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->string('reference', 40);
                $table->string('title');
                $table->text('scope_summary')->nullable();
                $table->text('value_proposition')->nullable();
                $table->string('version', 20)->default('1.0');
                $table->decimal('proposed_value', 15, 2)->default(0);
                $table->string('currency', 8)->default('UGX');
                $table->string('status', 40)->default('Draft')->index();
                $table->unsignedBigInteger('prepared_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'reference'], 'commercial_proposal_reference_unique');
            });
        }

        if (! Schema::hasTable('commercial_quotations')) {
            Schema::create('commercial_quotations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->unsignedBigInteger('proposal_id')->nullable()->index();
                $table->unsignedBigInteger('legacy_invoice_id')->nullable()->index();
                $table->string('reference', 40);
                $table->date('quotation_date');
                $table->date('valid_until')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('currency', 8)->default('UGX');
                $table->string('status', 40)->default('Draft')->index();
                $table->text('terms')->nullable();
                $table->unsignedBigInteger('prepared_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'reference'], 'commercial_quotation_reference_unique');
            });
        }

        if (! Schema::hasTable('commercial_contracts')) {
            Schema::create('commercial_contracts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->unsignedBigInteger('quotation_id')->nullable()->index();
                $table->string('reference', 40);
                $table->string('contract_title');
                $table->decimal('contract_value', 15, 2)->default(0);
                $table->string('currency', 8)->default('UGX');
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->string('payment_terms')->nullable();
                $table->string('status', 40)->default('Draft')->index();
                $table->unsignedBigInteger('prepared_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'reference'], 'commercial_contract_reference_unique');
            });
        }

        if (! Schema::hasTable('commercial_billing_requests')) {
            Schema::create('commercial_billing_requests', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->unsignedBigInteger('contract_id')->nullable()->index();
                $table->unsignedBigInteger('quotation_id')->nullable()->index();
                $table->string('reference', 40);
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('currency', 8)->default('UGX');
                $table->date('requested_invoice_date')->nullable();
                $table->string('billing_terms')->nullable();
                $table->text('instructions')->nullable();
                $table->string('status', 40)->default('Requested')->index();
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'reference'], 'commercial_billing_reference_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_billing_requests');
        Schema::dropIfExists('commercial_contracts');
        Schema::dropIfExists('commercial_quotations');
        Schema::dropIfExists('commercial_proposals');

        foreach (['commercial_opportunities', 'commercial_leads'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'campaign_id')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('campaign_id');
                });
            }
        }

        Schema::dropIfExists('commercial_campaigns');
    }
};
