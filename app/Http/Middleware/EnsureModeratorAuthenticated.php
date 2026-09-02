<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModeratorAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredSecret = config('game.moderator_secret');

        // Check X-Moderator-Key header
        $moderatorHeader = $request->header('X-Moderator-Key');
        if ($configuredSecret && $moderatorHeader && hash_equals($configuredSecret, $moderatorHeader)) {
            return $next($request);
        }

        // Check Bearer token if present
        $bearerToken = $request->bearerToken();
        if ($configuredSecret && $bearerToken && hash_equals($configuredSecret, $bearerToken)) {
            return $next($request);
        }

        // Check Sanctum authenticated user
        if ($request->user()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthorized: Valid moderator credentials (X-Moderator-Key header or Bearer token) required.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
