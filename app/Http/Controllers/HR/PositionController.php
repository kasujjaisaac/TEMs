<?php

namespace App\Http\Controllers\HR;

use App\Http\Requests\HR\StoreHrPositionRequest;
use App\Models\AuditLog;
use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PositionController extends HrController
{
    public function index(Request $request): View
    {
        $this->authorizeHr('hr.positions.view');

        return view('hr.positions.index', [
            'page_title' => 'Positions & Job Architecture | Texaro Technologies Limited',
            'positions' => HrPosition::with(['department', 'reportsTo'])
                ->where('tenant_id', $this->tenantId())
                ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('position_status', $status))
                ->orderBy('title')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeHr('hr.positions.manage');

        return view('hr.positions.form', [
            'page_title' => 'Create Position | Texaro Technologies Limited',
            'position' => new HrPosition(['employment_type' => 'Full time', 'position_status' => 'Planned', 'approved_headcount' => 1, 'filled_headcount' => 0]),
            'departments' => HrDepartment::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'positions' => HrPosition::where('tenant_id', $this->tenantId())->orderBy('title')->get(),
        ]);
    }

    public function store(StoreHrPositionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        HrDepartment::where('tenant_id', $this->tenantId())->findOrFail($data['department_id']);
        if (! empty($data['reports_to_position_id'])) {
            HrPosition::where('tenant_id', $this->tenantId())->findOrFail($data['reports_to_position_id']);
        }

        $position = HrPosition::create($data + [
            'tenant_id' => $this->tenantId(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->audit($request, 'created', $position, 'Created HR position ' . $position->code);

        return redirect()->route('hr.positions.show', $position)->with('success', 'Position created successfully.');
    }

    public function show(HrPosition $position): View
    {
        $this->authorizeHr('hr.positions.view');
        $this->ensureTenant($position);

        return view('hr.positions.show', [
            'page_title' => $position->title . ' | Position',
            'position' => $position->load(['department', 'reportsTo', 'directReports']),
        ]);
    }

    public function edit(HrPosition $position): View
    {
        $this->authorizeHr('hr.positions.manage');
        $this->ensureTenant($position);

        return view('hr.positions.form', [
            'page_title' => 'Edit ' . $position->title . ' | Texaro Technologies Limited',
            'position' => $position,
            'departments' => HrDepartment::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
            'positions' => HrPosition::where('tenant_id', $this->tenantId())->where('id', '!=', $position->id)->orderBy('title')->get(),
        ]);
    }

    public function update(StoreHrPositionRequest $request, HrPosition $position): RedirectResponse
    {
        $this->ensureTenant($position);
        $data = $request->validated();
        HrDepartment::where('tenant_id', $this->tenantId())->findOrFail($data['department_id']);
        if (! empty($data['reports_to_position_id'])) {
            abort_if((int) $data['reports_to_position_id'] === (int) $position->id, 422, 'A position cannot report to itself.');
            HrPosition::where('tenant_id', $this->tenantId())->findOrFail($data['reports_to_position_id']);
        }

        $before = $position->only(['title', 'department_id', 'reports_to_position_id', 'position_status', 'approved_headcount', 'filled_headcount']);
        $position->fill($data + ['updated_by' => Auth::id()])->save();

        $this->audit($request, 'updated', $position, 'Updated HR position ' . $position->code, [
            'before' => $before,
            'after' => $position->only(array_keys($before)),
        ]);

        return redirect()->route('hr.positions.show', $position)->with('success', 'Position updated successfully.');
    }

    private function audit(Request $request, string $action, object $subject, string $description, array $metadata = []): void
    {
        AuditLog::create([
            'tenant_id' => $this->tenantId(),
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => 'hr',
            'subject_type' => $subject::class,
            'subject_id' => $subject->id,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }
}
