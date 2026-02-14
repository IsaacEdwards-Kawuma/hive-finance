<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->header('X-Company-Id') ?? $request->input('company_id');
        if ($companyId && $request->user()) {
            $allowed = $request->user()->companies()->where('companies.id', $companyId)->exists();
            if ($allowed) {
                session(['current_company_id' => (int) $companyId]);
            }
        }
        return $next($request);
    }
}
