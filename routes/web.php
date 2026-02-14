<?php

use Illuminate\Support\Facades\Route;

Route::get('/portal', fn () => view('portal'))->name('portal');
Route::get('/portal/pay', fn () => view('portal-pay'))->name('portal.pay');
Route::get('/api/documentation', fn () => response()->file(public_path('openapi.json'), ['Content-Type' => 'application/json']));
Route::get('/api/docs', fn () => view('swagger-ui'));
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|up|portal).*$');
