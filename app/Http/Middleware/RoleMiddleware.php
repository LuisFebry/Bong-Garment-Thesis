<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    private const VALID_ROLES = ['Admin', 'Pemilik', 'Pemotong', 'QC', 'Gudang'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        $authRole = ($_COOKIE['bong_garment_auth_role'] ?? null)
            ?? $request->cookie('bong_garment_auth_role');

        $activeRole = ($_COOKIE['bong_garment_role'] ?? null)
            ?? $request->cookie('bong_garment_role')
            ?? $request->header('X-User-Role');

        if ($user) {
            $userRole = $user->role;
        } elseif ($authRole && in_array($authRole, self::VALID_ROLES, true)) {
            // Only an authenticated Admin is permitted to switch active role via the Peran Aktif selector
            if ($authRole === 'Admin' && $activeRole && in_array($activeRole, self::VALID_ROLES, true)) {
                $userRole = $activeRole;
            } else {
                $userRole = $authRole;
            }
        } elseif ($activeRole && in_array($activeRole, self::VALID_ROLES, true)) {
            $userRole = $activeRole;
        } else {
            $userRole = null;
        }

        // Unauthenticated visitors must log in with valid credentials first
        if (!$userRole) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi tidak ditemukan. Silakan login terlebih dahulu.',
                ], 401);
            }
            return redirect('/login');
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
