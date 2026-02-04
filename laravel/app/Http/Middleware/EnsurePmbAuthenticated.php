<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePmbAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        // Anda bisa sesuaikan key session yang Anda gunakan
        if (!$request->session()->has('pmb_auth')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect('/login');
        }

        return $next($request);
    }
}
