<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

final class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (\Illuminate\Http\JsonResponse|\Illuminate\Http\Response)  $next
     */
    public function handle(Request $request, Closure $next): \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (Exception) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        /** @var \Illuminate\Http\JsonResponse|\Illuminate\Http\Response $response */
        $response = $next($request);

        return $response;
    }
}
