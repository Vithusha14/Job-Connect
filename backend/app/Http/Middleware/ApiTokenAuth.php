<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ApiTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $header = $request->header('Authorization') ?: $request->query('token');
        $parsed = ApiTokenService::parse($header);

        if (!$parsed) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized. Please log in.'], 401);
        }

        $user = User::query()->find($parsed['user_id']);
        if (!$user || !$user->isActive()) {
            return response()->json(['ok' => false, 'error' => 'Account blocked or not found.'], 403);
        }

        if ($role && $user->role !== $role) {
            return response()->json(['ok' => false, 'error' => 'Access denied for this role.'], 403);
        }

        $request->attributes->set('auth_user', $user);
        auth()->setUser($user);

        return $next($request);
    }
}
