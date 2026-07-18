<?php

namespace App\Http\Controllers\Planning;

use App\Models\Planning\StrategicObjective;
use App\Models\Planning\StrategicPillar;
use App\Services\Planning\PlanningPerformanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ObjectiveController extends PlanningController
{
    public function index(PlanningPerformanceService $planning): View
    {
        $this->authorizePlanning('planning.strategy.view');
        $year = $planning->bootstrapTenant($this->tenantId());

        return view('planning.objectives.index', [
            'page_title' => 'Strategic Objectives | Texaro Technologies Limited',
            'planningYear' => $year,
            'pillars' => StrategicPillar::withCount('objectives')->where('tenant_id', $this->tenantId())->orderBy('code')->get(),
            'objectives' => StrategicObjective::with('pillar')->where('tenant_id', $this->tenantId())->orderBy('code')->paginate(15),
        ]);
    }

    public function store(Request $request, PlanningPerformanceService $planning): RedirectResponse
    {
        $this->authorizePlanning('planning.strategy.manage');
        $year = $planning->bootstrapTenant($this->tenantId());
        $data = $request->validate([
            'strategic_pillar_id' => ['nullable', 'integer'],
            'code' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'kpi' => ['nullable', 'string', 'max:255'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:80'],
            'weight' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'string', 'max:40'],
        ]);

        if (! empty($data['strategic_pillar_id'])) {
            StrategicPillar::where('tenant_id', $this->tenantId())->findOrFail($data['strategic_pillar_id']);
        }

        StrategicObjective::updateOrCreate(
            ['tenant_id' => $this->tenantId(), 'code' => $data['code']],
            $data + ['planning_year_id' => $year->id, 'owner_name' => Auth::user()?->name]
        );

        return redirect()->route('planning.objectives.index')->with('success', 'Strategic objective saved.');
    }
}
