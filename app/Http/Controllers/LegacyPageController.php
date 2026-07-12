<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class LegacyPageController extends Controller
{
    public function show(Request $request, string $page = 'dashboard')
    {
        $page = str_replace('-', '_', $page);

        if (! in_array($page, onyx_legacy_pages(), true)) {
            abort(404);
        }

        return view('legacy.' . $page);
    }
}
