<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddCorsHeadersToApiResponses
{
    /**
     * Add CORS headers to every API response so error responses (e.g. 500) also allow the frontend origin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->is('api/*') && !$request->is('sanctum/*')) {
            return $next($request);
        }

        $origin = $request->header('Origin');
        $allowed = $origin && $this->originAllowed($origin);

        // Preflight: respond immediately with 200 and CORS headers so browser sends the real request
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
            if ($allowed) {
                $this->addCorsHeaders($response, $origin);
            }
            return $response;
        }

        $response = $next($request);
        if ($allowed) {
            $this->addCorsHeaders($response, $origin);
        }
        return $response;
    }

    private function addCorsHeaders(Response $response, string $origin): void
    {
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Company-Id, Accept');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition');
    }

    private function originAllowed(string $origin): bool
    {
        $origin = rtrim($origin, '/');
        $allowed = config('cors.allowed_origins', []);
        if (in_array('*', $allowed) || in_array($origin, $allowed)) {
            return true;
        }
        foreach (config('cors.allowed_origins_patterns', []) as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }
        return false;
    }
}
