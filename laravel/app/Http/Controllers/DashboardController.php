<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // sudah auth
        if (Gate::allows('admin')) {
            return redirect()->route('admin.dashboard');
        }

        // user PMB
        if ($request->session()->has('pmb_source') && $request->session()->has('pmb_id')) {
            return redirect()->route('pmb.dashboard');
        }

        // user yang sudah auth tapi belum punya session PMB
        // kemungkinan admin biasa atau user yang belum lengkap session-nya
        // Jangan logout! Biarkan mereka akses dashboard biasa
        return view('dashboards.spa', ['mode' => 'user']);
    }

    public function admin()
    {
        return view('dashboards.spa', ['mode' => 'admin']);
    }

    public function pmb()
    {
        return view('dashboards.spa', ['mode' => 'pmb']);
    }
}
