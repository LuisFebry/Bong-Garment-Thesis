<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        $users = User::all(['id_user', 'username', 'nama_user', 'role']);
        return view('bong.login', compact('users'));
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $validated['username'])->first();

        if (!$user || (!Hash::check($validated['password'], $user->password) && $validated['password'] !== $user->password)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Username atau password tidak sesuai.',
                ], 401);
            }
            return back()->withErrors(['username' => 'Username atau password tidak sesuai.'])->withInput();
        }

        $minutes = 60 * 24 * 365;
        $redirectMap = [
            'Admin' => '/dashboard',
            'Pemilik' => '/dashboard',
            'Pemotong' => '/pemotongan',
            'QC' => '/qc',
            'Gudang' => '/penerimaan',
        ];
        $redirectTo = $redirectMap[$user->role] ?? '/dashboard';

        $response = $request->wantsJson()
            ? response()->json([
                'success' => true,
                'message' => "Selamat datang, {$user->nama_user}!",
                'user' => [
                    'id_user' => $user->id_user,
                    'username' => $user->username,
                    'nama_user' => $user->nama_user,
                    'role' => $user->role,
                ],
                'redirect' => $redirectTo,
            ])
            : redirect($redirectTo);

        return $response
            ->cookie('bong_garment_role', $user->role, $minutes, '/', null, false, false)
            ->cookie('bong_garment_user_id', (string) $user->id_user, $minutes, '/', null, false, false)
            ->cookie('bong_garment_user_name', $user->nama_user, $minutes, '/', null, false, false);
    }

    public function logout(Request $request)
    {
        $response = $request->wantsJson()
            ? response()->json(['success' => true, 'redirect' => '/login'])
            : redirect('/login');

        return $response
            ->withoutCookie('bong_garment_role', '/')
            ->withoutCookie('bong_garment_user_id', '/')
            ->withoutCookie('bong_garment_user_name', '/');
    }
}

