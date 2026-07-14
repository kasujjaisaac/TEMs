<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\PagePermissionMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegacyPageController extends Controller
{
    public function show(Request $request, string $page = 'dashboard')
    {
        $page = str_replace('-', '_', $page);

        if (! in_array($page, onyx_legacy_pages(), true)) {
            abort(404);
        }

        if (! Auth::check()) {
            return redirect()->route('login');
        }

        Role::ensureDefaultsForTenant(Auth::user()->tenant_id);

        $permission = PagePermissionMap::forPage($page);
        abort_unless(! $permission || Auth::user()->hasPermission($permission), 403);

        return view('legacy.' . $page);
    }
}
