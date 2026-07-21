<?php

namespace App\Services\Commercial;

use App\Models\Commercial\CommercialBillingRequest;
use App\Models\Commercial\CommercialContract;
use App\Models\Commercial\CommercialOpportunity;
use App\Models\Commercial\CommercialProposal;
use App\Models\Commercial\CommercialQuotation;
use App\Models\User;
use App\Services\Enterprise\DomainEventService;
use App\Services\Enterprise\NotificationService;
use Illuminate\Support\Facades\DB;

class CommercialRevenueLifecycleService
{
    public function __construct(private readonly CommercialNumberingService $numbering)
    {
    }

    public function createProposal(CommercialOpportunity $opportunity, User $user, array $data): CommercialProposal
    {
        return DB::transaction(function () use ($opportunity, $user, $data): CommercialProposal {
            $proposal = CommercialProposal::create([
                'tenant_id' => $opportunity->tenant_id,
                'opportunity_id' => $opportunity->id,
                'reference' => $this->numbering->next((int) $opportunity->tenant_id, 'proposal'),
                'title' => $data['title'],
                'scope_summary' => $data['scope_summary'] ?? null,
                'value_proposition' => $data['value_proposition'] ?? null,
                'version' => $data['version'] ?? '1.0',
                'proposed_value' => $data['proposed_value'] ?? $opportunity->estimated_value,
                'currency' => $opportunity->currency ?: 'UGX',
                'status' => 'Draft',
                'prepared_by' => $user->id,
            ]);

            $this->recordEvent('proposal.created', $proposal, $opportunity, $user);

            return $proposal;
        });
    }

    public function approveProposal(CommercialProposal $proposal, User $user): CommercialProposal
    {
        $proposal->forceFill(['status' => 'Approved', 'approved_by' => $user->id, 'approved_at' => now()])->save();
        $this->recordEvent('proposal.approved', $proposal, $proposal->opportunity, $user);

        return $proposal;
    }

    public function createQuotation(CommercialOpportunity $opportunity, User $user, array $data): CommercialQuotation
    {
        return DB::transaction(function () use ($opportunity, $user, $data): CommercialQuotation {
            $subtotal = (float) ($data['subtotal'] ?? $opportunity->estimated_value);
            $discount = (float) ($data['discount_amount'] ?? 0);
            $tax = (float) ($data['tax_amount'] ?? 0);
            $quotation = CommercialQuotation::create([
                'tenant_id' => $opportunity->tenant_id,
                'opportunity_id' => $opportunity->id,
                'proposal_id' => $data['proposal_id'] ?? null,
                'reference' => $this->numbering->next((int) $opportunity->tenant_id, 'quotation'),
                'quotation_date' => $data['quotation_date'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? now()->addDays(14)->toDateString(),
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total' => max(0, $subtotal - $discount + $tax),
                'currency' => $opportunity->currency ?: 'UGX',
                'status' => 'Draft',
                'terms' => $data['terms'] ?? null,
                'prepared_by' => $user->id,
            ]);

            $opportunity->forceFill([
                'current_stage' => 'Quotation',
                'probability' => max((int) $opportunity->probability, 70),
                'updated_by' => $user->id,
            ])->save();

            $this->recordEvent('quotation.created', $quotation, $opportunity, $user);

            return $quotation;
        });
    }

    public function approveQuotation(CommercialQuotation $quotation, User $user): CommercialQuotation
    {
        $quotation->forceFill(['status' => 'Approved', 'approved_by' => $user->id, 'approved_at' => now()])->save();
        $this->recordEvent('quotation.approved', $quotation, $quotation->opportunity, $user);

        return $quotation;
    }

    public function acceptQuotation(CommercialQuotation $quotation, User $user): CommercialQuotation
    {
        return DB::transaction(function () use ($quotation, $user): CommercialQuotation {
            $quotation->forceFill(['status' => 'Accepted', 'accepted_at' => now()])->save();
            $quotation->opportunity->forceFill([
                'current_stage' => 'Contracting',
                'probability' => max((int) $quotation->opportunity->probability, 85),
                'updated_by' => $user->id,
            ])->save();

            $this->recordEvent('quotation.accepted', $quotation, $quotation->opportunity, $user);

            return $quotation;
        });
    }

    public function createContract(CommercialOpportunity $opportunity, User $user, array $data): CommercialContract
    {
        return DB::transaction(function () use ($opportunity, $user, $data): CommercialContract {
            $contract = CommercialContract::create([
                'tenant_id' => $opportunity->tenant_id,
                'opportunity_id' => $opportunity->id,
                'quotation_id' => $data['quotation_id'] ?? null,
                'reference' => $this->numbering->next((int) $opportunity->tenant_id, 'contract'),
                'contract_title' => $data['contract_title'],
                'contract_value' => $data['contract_value'] ?? $opportunity->estimated_value,
                'currency' => $opportunity->currency ?: 'UGX',
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'status' => 'Draft',
                'prepared_by' => $user->id,
            ]);

            $opportunity->forceFill([
                'current_stage' => 'Contracting',
                'probability' => max((int) $opportunity->probability, 90),
                'updated_by' => $user->id,
            ])->save();

            $this->recordEvent('contract.created', $contract, $opportunity, $user);

            return $contract;
        });
    }

    public function signContract(CommercialContract $contract, User $user): CommercialContract
    {
        return DB::transaction(function () use ($contract, $user): CommercialContract {
            $contract->forceFill([
                'status' => 'Signed',
                'approved_by' => $contract->approved_by ?: $user->id,
                'approved_at' => $contract->approved_at ?: now(),
                'signed_at' => now(),
            ])->save();

            $contract->opportunity->forceFill([
                'current_stage' => 'Won',
                'probability' => 100,
                'won_at' => $contract->opportunity->won_at ?: now(),
                'updated_by' => $user->id,
            ])->save();

            $this->recordEvent('contract.signed', $contract, $contract->opportunity, $user);

            return $contract;
        });
    }

    public function createBillingRequest(CommercialOpportunity $opportunity, User $user, array $data): CommercialBillingRequest
    {
        return DB::transaction(function () use ($opportunity, $user, $data): CommercialBillingRequest {
            $billing = CommercialBillingRequest::create([
                'tenant_id' => $opportunity->tenant_id,
                'opportunity_id' => $opportunity->id,
                'contract_id' => $data['contract_id'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
                'reference' => $this->numbering->next((int) $opportunity->tenant_id, 'billing'),
                'amount' => $data['amount'],
                'currency' => $opportunity->currency ?: 'UGX',
                'requested_invoice_date' => $data['requested_invoice_date'] ?? now()->toDateString(),
                'billing_terms' => $data['billing_terms'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'status' => 'Requested',
                'requested_by' => $user->id,
            ]);

            $opportunity->forceFill([
                'sales_handoff_status' => 'Billing Requested',
                'updated_by' => $user->id,
            ])->save();

            $this->recordEvent('billing.requested', $billing, $opportunity, $user);
            app(NotificationService::class)->notify(
                null,
                (int) $opportunity->tenant_id,
                'Billing request created',
                $billing->reference . ' is ready for Finance review.',
                ['source_module' => 'Commercial Operations', 'type' => 'billing', 'severity' => 'Info', 'action_url' => route('commercial.opportunities.show', $opportunity)]
            );

            return $billing;
        });
    }

    private function recordEvent(string $name, object $subject, CommercialOpportunity $opportunity, User $user): void
    {
        app(DomainEventService::class)->record($name, 'Commercial Operations', $subject, [
            'opportunity_id' => $opportunity->id,
            'opportunity_reference' => $opportunity->reference,
        ], (int) $opportunity->tenant_id, $user);
    }
}
