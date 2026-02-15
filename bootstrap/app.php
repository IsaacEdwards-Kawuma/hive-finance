<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->job(new \App\Jobs\ProcessRecurringInvoicesJob)->daily();
        $schedule->job(new \App\Jobs\ProcessRecurringBillsJob)->daily();
        $schedule->job(new \App\Jobs\PaymentReminderJob)->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'company' => \App\Http\Middleware\SetCompanyFromRequest::class,
            'portal.customer' => \App\Http\Middleware\EnsurePortalCustomer::class,
            'api.deprecation' => \App\Http\Middleware\AddApiDeprecationHeaders::class,
        ]);
        $middleware->api(prepend: [\App\Http\Middleware\AddCorsHeadersToApiResponses::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (!$request->is('api/*') && !$request->is('sanctum/*')) {
                return null;
            }
            $origin = $request->header('Origin');
            $corsHeaders = [];
            if ($origin) {
                $allowed = config('cors.allowed_origins', []);
                $patterns = config('cors.allowed_origins_patterns', []);
                $ok = in_array('*', $allowed) || in_array($origin, $allowed);
                if (!$ok) {
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $origin)) {
                            $ok = true;
                            break;
                        }
                    }
                }
                if ($ok) {
                    $corsHeaders = [
                        'Access-Control-Allow-Origin' => $origin,
                        'Access-Control-Allow-Credentials' => 'true',
                        'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Company-Id, Accept',
                    ];
                }
            }
            if ($e instanceof ValidationException) {
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422)->withHeaders($corsHeaders);
            }
            if ($e instanceof HttpException) {
                return response()->json(['message' => $e->getMessage()], $e->getStatusCode())->withHeaders($corsHeaders);
            }
            $message = config('app.debug') ? $e->getMessage() : 'Server Error';
            return response()->json(['message' => $message], 500)->withHeaders($corsHeaders);
        });
    })->create();
