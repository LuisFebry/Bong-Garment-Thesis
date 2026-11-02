<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // If running in demo mode or user not resolved through standard session, check header or request param
        $userRole = $user ? $user->role : ($request->header('X-User-Role') ?? $request->input('active_role', 'Admin'));

        if (!empty($roles) && !in_array($userRole, $roles) && $userRole !== 'Admin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => "Akses ditolak. Peran '{$userRole}' tidak memiliki hak akses untuk tindakan ini.",
                ], 403);
            }
            abort(403, "Akses ditolak untuk peran {$userRole}.");
        }

        // Pemilik is strictly Read-Only: Reject mutating requests
        if ($userRole === 'Pemilik' && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => "Peran Pemilik berada dalam Mode Audit (Read-Only). Mutasi data tidak diizinkan.",
                ], 403);
            }
            abort(403, "Mode Audit Pemilik: Perubahan data tidak diizinkan.");
        }

        return $next($request);
    }
}

