<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        // Mendapatkan pengguna yang sedang login
        $user = Auth::user();

        // Memeriksa apakah user ada dan memiliki role yang sesuai
        if (!$user || !$user->role || $user->role->name !== $role) {
            return response()->json([
                'message' => 'You do not have permission to access this resource.',
                'status_code' => 403
            ], 403);
        }

        return $next($request);
    }
}
