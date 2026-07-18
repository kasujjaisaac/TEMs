<?php

namespace App\Http\Controllers\Planning;

use App\Services\Planning\PlanningPerformanceService;
use Illuminate\View\View;

class DashboardController extends PlanningController
{
    public function __invoke(PlanningPerformanceService $planning): View
    {
        $this->authorizePlanning('planning.dashboard.view');

        return view('planning.dashboard', [
            'page_title' => 'Planning & Performance | Texaro Technologies Limited',
            ...$planning->dashboard($this->tenantId()),
        ]);
    }
}
