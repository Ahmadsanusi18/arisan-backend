<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'superadmin') {
            return response()->json([
                'message' => 'Akses khusus superadmin.',
            ], 403);
        }

        return $next($request);
    }
}