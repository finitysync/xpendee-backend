<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Account suspended. Please contact support.',
                'data' => null
            ], 403);
        }

        return $next($request);
    }
}
