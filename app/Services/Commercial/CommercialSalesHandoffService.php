<?php

namespace App\Services\Commercial;

use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialSalesHandoff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class CommercialSalesHandoffService
{
    /**
     * @return array{handoff: CommercialSalesHandoff, customer_id: int, quotation_id: int, created_customer: bool, created_quotation: bool}
     */
    public function handoffToSales(CommercialOpportunity $opportunity, User $user, array $data = []): array
    {
        $this->assertSalesBridgeSchema();

        return DB::transaction(function () use ($opportunity, $user, $data): array {
            $opportunity->loadMissing(['organization', 'primaryStakeholder', 'assignedEmployee']);
            $organization = $opportunity->organization;
            $tenantId = (int) $opportunity->tenant_id;

            $createdCustomer = false;
            $previousCustomerId = (int) ($organization->legacy_customer_id ?: 0);
            if ($previousCustomerId <= 0 || ! $this->customerExists($tenantId, $previousCustomerId)) {
                $createdCustomer = true;
            }
            $customerId = app(CommercialLegacyCustomerBridgeService::class)->syncOrganization($organization, $user);
            $organization->refresh();

            $handoff = CommercialSalesHandoff::firstOrCreate(
                ['tenant_id' => $tenantId, 'opportunity_id' => $opportunity->id],
                [
                    'organization_id' => $organization->id,
                    'legacy_customer_id' => $customerId,
                    'status' => 'Drafting Quotation',
                    'handoff_value' => $opportunity->estimated_value,
                    'currency' => $opportunity->currency ?: 'UGX',
                    'sales_owner' => $user->name,
                    'handoff_summary' => $data['handoff_summary'] ?? $this->defaultSummary($opportunity),
                    'sales_instructions' => $data['sales_instructions'] ?? null,
                    'created_by' => $user->id,
                    'handed_off_at' => now(),
                ]
            );

            $createdQuotation = false;
            $quotationId = (int) ($handoff->quotation_id ?: $opportunity->legacy_quotation_id ?: 0);
            if ($quotationId <= 0 || ! $this->quotationExists($tenantId, $quotationId)) {
                $quotationId = $this->createQuotation($opportunity, $handoff, $customerId, $user, $data);
                $createdQuotation = true;
            }

            $handoff->forceFill([
                'organization_id' => $organization->id,
                'legacy_customer_id' => $customerId,
                'quotation_id' => $quotationId,
                'status' => 'Quotation Drafted',
                'handoff_value' => $opportunity->estimated_value,
                'currency' => $opportunity->currency ?: 'UGX',
                'sales_owner' => $user->name,
                'handoff_summary' => $data['handoff_summary'] ?? $handoff->handoff_summary ?? $this->defaultSummary($opportunity),
                'sales_instructions' => $data['sales_instructions'] ?? $handoff->sales_instructions,
                'handed_off_at' => $handoff->handed_off_at ?: now(),
            ])->save();

            $opportunity->forceFill([
                'current_stage' => $opportunity->current_stage === 'Won' ? 'Won' : 'Sales Handoff',
                'probability' => max((int) $opportunity->probability, 90),
                'sales_handoff_status' => 'Quotation Drafted',
                'sales_handoff_at' => now(),
                'legacy_quotation_id' => $quotationId,
                'won_at' => $opportunity->won_at ?: now(),
                'updated_by' => $user->id,
            ])->save();

            return [
                'handoff' => $handoff,
                'customer_id' => $customerId,
                'quotation_id' => $quotationId,
                'created_customer' => $createdCustomer,
                'created_quotation' => $createdQuotation,
            ];
        });
    }

    private function createCustomer(CommercialOpportunity $opportunity, User $user): int
    {
        $organization = $opportunity->organization;
        $stakeholder = $opportunity->primaryStakeholder;
        $tenantId = (int) $opportunity->tenant_id;

        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_code' => $this->nextCustomerCode($tenantId),
            'name' => $organization->trading_name ?: $organization->legal_name,
            'company_name' => $organization->legal_name,
            'contact_person' => $stakeholder?->full_name,
            'customer_type' => $organization->organization_type ?: 'commercial',
            'email' => $organization->primary_email ?: $stakeholder?->email,
            'phone' => $organization->primary_telephone ?: $stakeholder?->telephone,
            'address' => $organization->physical_address,
            'billing_address' => $organization->postal_address ?: $organization->physical_address,
            'city' => $organization->city,
            'country' => $organization->country,
            'tin_number' => $organization->tin,
            'customer_group' => $organization->customer_category ?: 'Commercial',
            'payment_terms' => $organization->payment_terms ?: 'Net 30',
            'credit_status' => $organization->credit_status ?: 'pending_review',
            'account_manager' => $opportunity->assignedEmployee?->name ?: $user->name,
            'customer_source' => $opportunity->opportunity_source ?: $organization->acquisition_source ?: 'Commercial Operations',
            'internal_notes' => 'Created from commercial opportunity ' . $opportunity->reference . '.',
            'credit_limit' => 0,
            'credit_balance' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createQuotation(CommercialOpportunity $opportunity, CommercialSalesHandoff $handoff, int $customerId, User $user, array $data): int
    {
        $tenantId = (int) $opportunity->tenant_id;
        $subtotal = max(0, (float) $opportunity->estimated_value);
        $quotationId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_number' => $this->nextDocumentNumber($tenantId, 'quotation'),
            'invoice_type' => 'quotation',
            'customer_id' => $customerId,
            'invoice_date' => now()->toDateString(),
            'due_date' => $opportunity->expected_close_date?->toDateString() ?: now()->addDays(14)->toDateString(),
            'notes' => $data['quotation_notes'] ?? $this->defaultQuotationNotes($opportunity),
            'terms' => $opportunity->organization?->payment_terms ?: 'Net 30',
            'salesperson' => $user->name,
            'branch_name' => null,
            'customer_reference' => $opportunity->reference,
            'discount' => 0,
            'delivery_charge' => 0,
            'subtotal' => $subtotal,
            'tax' => 0,
            'total' => $subtotal,
            'status' => 'draft',
            'source_invoice_id' => null,
            'commercial_opportunity_id' => $opportunity->id,
            'commercial_handoff_id' => $handoff->id,
            'stock_posted' => false,
            'accounting_posted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_lines')->insert([
            'tenant_id' => $tenantId,
            'invoice_id' => $quotationId,
            'product_id' => null,
            'description' => $this->quotationLineDescription($opportunity),
            'unit_price' => $subtotal,
            'quantity' => 1,
            'tax_rate' => 0,
            'line_total' => $subtotal,
            'created_at' => now(),
        ]);

        return $quotationId;
    }

    private function customerExists(int $tenantId, int $customerId): bool
    {
        return DB::table('customers')->where('tenant_id', $tenantId)->where('id', $customerId)->exists();
    }

    private function quotationExists(int $tenantId, int $quotationId): bool
    {
        return DB::table('invoices')->where('tenant_id', $tenantId)->where('id', $quotationId)->where('invoice_type', 'quotation')->exists();
    }

    private function nextCustomerCode(int $tenantId): string
    {
        $next = DB::table('customers')->where('tenant_id', $tenantId)->count() + 1;

        return sprintf('CUS-%05d', $next);
    }

    private function nextDocumentNumber(int $tenantId, string $type): string
    {
        $prefix = match ($type) {
            'quotation' => 'QT',
            default => 'INV',
        };
        $year = now()->format('Y');
        $next = DB::table('invoices')->where('tenant_id', $tenantId)->where('invoice_type', $type)->count() + 1;

        return sprintf('%s-%s-%05d', $prefix, $year, $next);
    }

    private function defaultSummary(CommercialOpportunity $opportunity): string
    {
        return trim(implode("\n", array_filter([
            'Opportunity: ' . $opportunity->reference . ' - ' . $opportunity->title,
            'Customer need: ' . $opportunity->customer_need,
            'Proposed solution: ' . $opportunity->proposed_solution,
            'Decision process: ' . $opportunity->decision_process,
        ])));
    }

    private function defaultQuotationNotes(CommercialOpportunity $opportunity): string
    {
        return trim(implode("\n\n", array_filter([
            'Prepared from commercial opportunity ' . $opportunity->reference . '.',
            $opportunity->proposed_solution,
            $opportunity->commercial_strategy ? 'Commercial strategy: ' . $opportunity->commercial_strategy : null,
        ])));
    }

    private function quotationLineDescription(CommercialOpportunity $opportunity): string
    {
        return trim(($opportunity->product_or_service ?: $opportunity->title) . "\n" . ($opportunity->proposed_solution ?: $opportunity->customer_need ?: 'Commercial opportunity package.'));
    }

    private function assertSalesBridgeSchema(): void
    {
        $required = [
            'customers' => ['tenant_id', 'name', 'company_name', 'customer_code'],
            'invoices' => ['tenant_id', 'invoice_number', 'invoice_type', 'customer_id', 'commercial_opportunity_id', 'commercial_handoff_id'],
            'invoice_lines' => ['tenant_id', 'invoice_id', 'description', 'line_total'],
        ];

        foreach ($required as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new LogicException("Missing sales bridge table [{$table}]. Run migrations before handing opportunities to Sales.");
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new LogicException("Missing sales bridge column [{$table}.{$column}]. Run migrations before handing opportunities to Sales.");
                }
            }
        }
    }
}
