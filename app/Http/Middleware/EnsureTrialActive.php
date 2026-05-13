<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTrialActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->status === 'trial' && $user->trial_ends_at && $user->trial_ends_at->isPast()) {
            if (!$request->isMethod('GET')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trial expired. Please upgrade.',
                    'data' => null
                ], 402);
            }
        }

        return $next($request);
    }
}
