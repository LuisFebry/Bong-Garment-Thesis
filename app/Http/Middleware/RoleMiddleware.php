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

        // Check header, then raw cookie, then Laravel cookie, then request parameter, default to 'Admin'
        $userRole = $user ? $user->role : (
            $request->header('X-User-Role')
            ?? ($_COOKIE['bong_garment_role'] ?? null)
            ?? $request->cookie('bong_garment_role')
            ?? $request->input('active_role', 'Admin')
        );

        $validRoles = ['Admin', 'Pemilik', 'Pemotong', 'QC', 'Gudang'];
        if (!in_array($userRole, $validRoles, true)) {
            $userRole = 'Admin';
        }

        // If specific roles are required and user is neither in allowed list nor Admin
        if (!empty($roles) && !in_array($userRole, $roles, true) && $userRole !== 'Admin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => "Akses ditolak. Peran '{$userRole}' tidak memiliki hak akses untuk tindakan ini.",
                ], 403);
            }
            return response()->view('errors.403_role', [
                'userRole' => $userRole,
                'allowedRoles' => $roles,
                'requestedUrl' => $request->path(),
            ], 403);
        }

        // Pemilik is strictly Read-Only: Reject mutating requests
        if ($userRole === 'Pemilik' && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => "Peran Pemilik berada dalam Mode Audit (Read-Only). Mutasi data tidak diizinkan.",
                ], 403);
            }
            return response()->view('errors.403_role', [
                'userRole' => $userRole,
                'allowedRoles' => ['Mode Audit (Read-Only)'],
                'requestedUrl' => $request->path(),
                'customMessage' => 'Peran Pemilik berada dalam Mode Audit (Read-Only). Perubahan atau penambahan data tidak diizinkan.'
            ], 403);
        }

        return $next($request);
    }
}
