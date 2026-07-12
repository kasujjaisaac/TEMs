<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchasesController extends Controller
{
    public function index()
    {
        $tenant = DB::table('tenants')->select('id')->first();
        $tenant_id = $tenant ? $tenant->id : 1;

        $purchases = DB::table('purchases')->where('tenant_id', $tenant_id)->get();
        return view('pages.purchases', compact('purchases'));
    }
}
