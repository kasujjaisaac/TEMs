<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuppliersController extends Controller
{
    public function index()
    {
        $tenant = DB::table('tenants')->select('id')->first();
        $tenant_id = $tenant ? $tenant->id : 1;

        $suppliers = DB::table('suppliers')->where('tenant_id', $tenant_id)->get();
        return view('pages.suppliers', compact('suppliers'));
    }
}
