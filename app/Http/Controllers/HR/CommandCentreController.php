<?php

namespace App\Http\Controllers\HR;

use App\Models\HR\HrDepartment;
use App\Models\HR\HrPosition;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommandCentreController extends HrController
{
    public function __invoke(): View
    {
        $this->authorizeHr('hr.command.view');
        $tenantId = $this->tenantId();
        $employees = DB::table('hr_employees')->where('tenant_id', $tenantId);
        $positions = HrPosition::where('tenant_id', $tenantId);

        return view('hr.command', [
            'page_title' => 'HR Command Centre | Texaro Technologies Limited',
            'metrics' => [
                'employees' => (clone $employees)->count(),
                'active_employees' => (clone $employees)->where('status', 'Active')->count(),
                'departments' => HrDepartment::where('tenant_id', $tenantId)->count(),
                'active_departments' => HrDepartment::where('tenant_id', $tenantId)->where('status', 'Active')->count(),
                'positions' => (clone $positions)->count(),
                'approved_headcount' => (clone $positions)->sum('approved_headcount'),
                'filled_headcount' => (clone $positions)->sum('filled_headcount'),
                'vacancies' => (clone $positions)->get()->sum->vacancy_count,
            ],
            'departments' => HrDepartment::withCount('positions')->where('tenant_id', $tenantId)->orderBy('name')->limit(8)->get(),
            'positions' => HrPosition::with('department')->where('tenant_id', $tenantId)->orderByDesc('updated_at')->limit(8)->get(),
        ]);
    }
}
