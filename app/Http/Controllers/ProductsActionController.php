<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductsActionController extends Controller
{
    public function show(Request $request)
    {
        $action = $request->query('action', 'add');
        return view('pages.products_action', compact('action'));
    }

    public function store(Request $request)
    {
        // Minimal placeholder: validate and insert product
        $data = $request->validate([
            'name' => 'required|string',
            'buying_price' => 'nullable|numeric',
            'current_stock' => 'nullable|integer'
        ]);

        $tenant = DB::table('tenants')->select('id')->first();
        $tenant_id = $tenant ? $tenant->id : 1;

        $data['tenant_id'] = $tenant_id;
        DB::table('products')->insert($data);

        return redirect('/products');
    }
}
