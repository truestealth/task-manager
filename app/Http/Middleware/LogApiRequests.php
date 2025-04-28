<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        Log::channel('api')->info('API Request', [
            'method' => $request->method(),
            'uri' => $request->path(),
            'user_id' => auth()->id(),
            'status' => $response->getStatusCode(),
            'duration' => $duration,
            'ip' => $request->ip(),
        ]);

        return $response;
    }
}
