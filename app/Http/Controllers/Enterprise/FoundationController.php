<?php

namespace App\Http\Controllers\Enterprise;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\DocumentRecord;
use App\Models\DomainEvent;
use App\Models\SystemNotification;
use App\Services\Enterprise\ApprovalService;
use App\Services\Enterprise\CompanySettingsService;
use App\Services\Enterprise\DomainEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FoundationController extends Controller
{
    public function index(CompanySettingsService $settings): View
    {
        $this->authorizeFoundation('foundation.view');
        $tenantId = $this->tenantId();

        return view('foundation.dashboard', [
            'page_title' => 'Enterprise Foundation | Texaro Technologies Limited',
            'settings' => $settings->settings($tenantId),
            'approvals' => ApprovalRequest::with(['requester', 'reviewer'])->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'notifications' => SystemNotification::with('user')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'events' => DomainEvent::with('actor')->where('tenant_id', $tenantId)->latest('occurred_at')->limit(12)->get(),
            'documents' => DocumentRecord::with('owner')->where('tenant_id', $tenantId)->latest()->limit(12)->get(),
            'metrics' => [
                'pending_approvals' => ApprovalRequest::where('tenant_id', $tenantId)->where('status', 'Pending')->count(),
                'unread_notifications' => SystemNotification::where('tenant_id', $tenantId)->whereNull('read_at')->count(),
                'events_today' => DomainEvent::where('tenant_id', $tenantId)->whereDate('occurred_at', today())->count(),
                'document_records' => DocumentRecord::where('tenant_id', $tenantId)->count(),
            ],
        ]);
    }

    public function updateCompany(Request $request, CompanySettingsService $settings): RedirectResponse
    {
        $this->authorizeFoundation('foundation.company.manage');

        $data = $request->validate([
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:80'],
            'company_country' => ['nullable', 'string', 'max:120'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'company_logo' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:8'],
            'fiscal_year_start' => ['nullable', 'date'],
            'approval_policy' => ['nullable', 'string', 'max:2000'],
            'notification_policy' => ['nullable', 'string', 'max:2000'],
            'document_policy' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings->update($this->tenantId(), $data, $request->user());
        $this->audit($request, 'updated', 'foundation', 'Updated company foundation settings', ['updated_keys' => array_keys($data)]);

        return back()->with('success', 'Company foundation settings saved.');
    }

    public function storeApproval(Request $request, ApprovalService $approvals): RedirectResponse
    {
        $this->authorizeFoundation('foundation.approvals.manage');

        $data = $request->validate([
            'module' => ['required', 'string', 'max:80'],
            'request_type' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(['Low', 'Normal', 'High', 'Critical'])],
        ]);

        $approval = $approvals->request($this->tenantId(), $data['module'], $data['request_type'], $data['title'], [
            'summary' => $data['summary'] ?? null,
            'priority' => $data['priority'],
            'requested_by' => Auth::id(),
            'actor' => $request->user(),
        ]);

        $this->audit($request, 'created', 'approvals', 'Created approval request ' . $approval->title);

        return back()->with('success', 'Approval request created.');
    }

    public function decideApproval(Request $request, ApprovalRequest $approval, ApprovalService $approvals): RedirectResponse
    {
        $this->authorizeFoundation('foundation.approvals.manage');
        $this->ensureTenantRecord($approval);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['Approved', 'Rejected'])],
            'decision_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $approvals->decide($approval, $data['decision'], $request->user(), $data['decision_notes'] ?? null);
        $this->audit($request, strtolower($data['decision']), 'approvals', $data['decision'] . ' approval request ' . $approval->title);

        return back()->with('success', 'Approval decision recorded.');
    }

    public function markNotificationRead(Request $request, SystemNotification $notification): RedirectResponse
    {
        $this->authorizeFoundation('foundation.notifications.view');
        $this->ensureTenantRecord($notification);

        $notification->forceFill(['read_at' => now()])->save();
        app(DomainEventService::class)->record('notification.read', 'Enterprise Foundation', $notification, [], $this->tenantId(), $request->user());

        return back()->with('success', 'Notification marked as read.');
    }

    private function authorizeFoundation(string $permission): void
    {
        abort_unless(Auth::user()?->hasPermission($permission), 403);
    }

    private function tenantId(): int
    {
        return (int) Auth::user()->tenant_id;
    }

    private function ensureTenantRecord(object $record): void
    {
        abort_unless((int) $record->tenant_id === $this->tenantId(), 404);
    }

    private function audit(Request $request, string $action, string $module, string $description, array $metadata = []): void
    {
        AuditLog::create([
            'tenant_id' => $this->tenantId(),
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }
}
