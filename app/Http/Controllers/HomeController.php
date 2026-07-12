<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $tenant = DB::table('tenants')->select('id')->first();
        $tenant_id = $tenant ? $tenant->id : null;

        $nav_query = $tenant_id ? ('?tenant_id=' . $tenant_id . '&') : '?';

        return view('pages.landing', compact('tenant_id', 'nav_query'));
    }
}
