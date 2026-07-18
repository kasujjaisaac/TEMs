<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_sales_handoffs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('opportunity_id')->constrained('commercial_opportunities')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('legacy_customer_id')->nullable()->index();
            $table->unsignedBigInteger('quotation_id')->nullable()->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->string('status', 80)->default('Quotation Drafted');
            $table->decimal('handoff_value', 15, 2)->default(0);
            $table->string('currency', 8)->default('UGX');
            $table->string('sales_owner', 180)->nullable();
            $table->text('handoff_summary')->nullable();
            $table->text('sales_instructions')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamp('handed_off_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'opportunity_id'], 'commercial_sales_handoff_opp_unique');
        });

        Schema::table('commercial_opportunities', function (Blueprint $table): void {
            if (! Schema::hasColumn('commercial_opportunities', 'sales_handoff_status')) {
                $table->string('sales_handoff_status', 80)->nullable()->after('lost_at');
            }
            if (! Schema::hasColumn('commercial_opportunities', 'sales_handoff_at')) {
                $table->timestamp('sales_handoff_at')->nullable()->after('sales_handoff_status');
            }
            if (! Schema::hasColumn('commercial_opportunities', 'legacy_quotation_id')) {
                $table->unsignedBigInteger('legacy_quotation_id')->nullable()->index()->after('sales_handoff_at');
            }
            if (! Schema::hasColumn('commercial_opportunities', 'legacy_invoice_id')) {
                $table->unsignedBigInteger('legacy_invoice_id')->nullable()->index()->after('legacy_quotation_id');
            }
        });

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table): void {
                if (! Schema::hasColumn('invoices', 'commercial_opportunity_id')) {
                    $table->unsignedBigInteger('commercial_opportunity_id')->nullable()->index()->after('source_invoice_id');
                }
                if (! Schema::hasColumn('invoices', 'commercial_handoff_id')) {
                    $table->unsignedBigInteger('commercial_handoff_id')->nullable()->index()->after('commercial_opportunity_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table): void {
                foreach (['commercial_handoff_id', 'commercial_opportunity_id'] as $column) {
                    if (Schema::hasColumn('invoices', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::table('commercial_opportunities', function (Blueprint $table): void {
            foreach (['legacy_invoice_id', 'legacy_quotation_id', 'sales_handoff_at', 'sales_handoff_status'] as $column) {
                if (Schema::hasColumn('commercial_opportunities', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('commercial_sales_handoffs');
    }
};
