<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->header(key: 'X-API-Key') ?? $request->query(key: 'api_key');
        $expected = env(key: 'API_KEY');

        // If no API key configured, allow all (dev convenience)
        if (!$expected) {
            return $next($request);
        }

        if ($provided && hash_equals(known_string: (string) $expected, user_string: (string) $provided)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Unauthorized',
        ], 401);
    }
}
