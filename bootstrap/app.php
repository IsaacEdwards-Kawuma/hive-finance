<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
