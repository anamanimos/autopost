<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak: Hanya Administrator yang diizinkan.',
                ], 403);
            }

            abort(403, 'Akses ditolak: Hanya Administrator yang diizinkan mengakses halaman ini.');
        }

        return $next($request);
    }
}
