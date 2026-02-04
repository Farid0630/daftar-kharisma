<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PmbAuthSession
{
    public function handle(Request $request, Closure $next)
    {
        // If a PMB session exists, allow access
        if ($request->session()->has('pmb_source') && $request->session()->has('pmb_id')) {
            return $next($request);
        }

        // Allow access if user is Laravel authenticated (normal user login)
        if (Auth::check()) {
            return $next($request);
        }

        // No auth at all
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return redirect('/login');
    }
}
