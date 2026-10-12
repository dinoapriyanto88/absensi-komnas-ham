<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()->isSuperadmin()
            ? redirect()->route('superadmin.index')
            : redirect()->route('admin.index');
    }
}
