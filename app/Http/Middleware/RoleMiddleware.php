<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Allow only the supplied roles on Sanctum-authenticated routes.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'Unauthorized role'], 403);
        }

        return $next($request);
    }
}
