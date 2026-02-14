<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddApiDeprecationHeaders
{
    /**
     * Add X-API-Deprecated and X-API-Sunset headers for deprecated API versions.
     * Not applied to any route by default; attach to a route group when a version is deprecated.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $sunsetDate = null): Response
    {
        $response = $next($request);

        $response->headers->set('X-API-Deprecated', 'true');
        $sunset = $sunsetDate ?? config('api.deprecation_sunset_date', '');
        if ($sunset !== '') {
            $response->headers->set('X-API-Sunset', $sunset);
        }

        return $response;
    }
}
