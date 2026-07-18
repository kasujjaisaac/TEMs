<?php

namespace App\Http\Controllers\HR;

use App\Http\Requests\HR\StoreHrDepartmentRequest;
use App\Models\AuditLog;
use App\Models\HR\HrDepartment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DepartmentController extends HrController
{
    public function index(Request $request): View
    {
        $this->authorizeHr('hr.structure.view');

        return view('hr.departments.index', [
            'page_title' => 'Departments & Teams | Texaro Technologies Limited',
            'departments' => HrDepartment::with(['parent', 'positions'])
                ->where('tenant_id', $this->tenantId())
                ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeHr('hr.structure.manage');

        return view('hr.departments.form', [
            'page_title' => 'Create Department | Texaro Technologies Limited',
            'department' => new HrDepartment(['type' => 'Department', 'status' => 'Proposed']),
            'parents' => HrDepartment::where('tenant_id', $this->tenantId())->orderBy('name')->get(),
        ]);
    }

    public function store(StoreHrDepartmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['parent_id'])) {
            HrDepartment::where('tenant_id', $this->tenantId())->findOrFail($data['parent_id']);
        }

        $department = HrDepartment::create($data + [
            'tenant_id' => $this->tenantId(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->audit($request, 'created', $department, 'Created HR department ' . $department->code);

        return redirect()->route('hr.departments.show', $department)->with('success', 'Department created successfully.');
    }

    public function show(HrDepartment $department): View
    {
        $this->authorizeHr('hr.structure.view');
        $this->ensureTenant($department);

        return view('hr.departments.show', [
            'page_title' => $department->name . ' | Department',
            'department' => $department->load(['parent', 'children', 'positions.reportsTo']),
        ]);
    }

    public function edit(HrDepartment $department): View
    {
        $this->authorizeHr('hr.structure.manage');
        $this->ensureTenant($department);

        return view('hr.departments.form', [
            'page_title' => 'Edit ' . $department->name . ' | Texaro Technologies Limited',
            'department' => $department,
            'parents' => HrDepartment::where('tenant_id', $this->tenantId())->where('id', '!=', $department->id)->orderBy('name')->get(),
        ]);
    }

    public function update(StoreHrDepartmentRequest $request, HrDepartment $department): RedirectResponse
    {
        $this->ensureTenant($department);
        $data = $request->validated();
        if (! empty($data['parent_id'])) {
            abort_if((int) $data['parent_id'] === (int) $department->id, 422, 'A department cannot be its own parent.');
            HrDepartment::where('tenant_id', $this->tenantId())->findOrFail($data['parent_id']);
        }

        $before = $department->only(['name', 'parent_id', 'status', 'type']);
        $department->fill($data + ['updated_by' => Auth::id()])->save();

        $this->audit($request, 'updated', $department, 'Updated HR department ' . $department->code, [
            'before' => $before,
            'after' => $department->only(array_keys($before)),
        ]);

        return redirect()->route('hr.departments.show', $department)->with('success', 'Department updated successfully.');
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
