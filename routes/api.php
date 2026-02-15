<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Deploy check: open in browser to verify DB and APP_KEY (no secrets exposed)
Route::get('/deploy-check', function () {
    $checks = ['app_key' => !empty(config('app.key')), 'database' => false];
    try {
        DB::connection()->getPdo();
        DB::connection()->getDatabaseName();
        $checks['database'] = true;
    } catch (\Throwable $e) {
        $checks['database_error'] = $e->getMessage();
    }
    $checks['ok'] = $checks['app_key'] && $checks['database'];
    return response()->json($checks);
});

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/forgot-password', [\App\Http\Controllers\Api\ForgotPasswordController::class, '__invoke']);
Route::post('/reset-password', [\App\Http\Controllers\Api\ResetPasswordController::class, '__invoke']);
Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Api\EmailVerificationController::class, 'verifyFromLink'])
    ->name('api.verification.verify');

Route::post('stripe/webhook', \App\Http\Controllers\Api\StripeWebhookController::class)->middleware('throttle:120,1')->name('api.stripe.webhook');

Route::prefix('portal')->group(function () {
    Route::get('companies', [\App\Http\Controllers\Api\Portal\CompanyController::class, 'index']);
    Route::post('login', [\App\Http\Controllers\Api\Portal\AuthController::class, 'login']);
    Route::middleware(['auth:sanctum', 'portal.customer'])->group(function () {
        Route::get('me', [\App\Http\Controllers\Api\Portal\AuthController::class, 'me']);
        Route::post('logout', [\App\Http\Controllers\Api\Portal\AuthController::class, 'logout']);
        Route::get('invoices', [\App\Http\Controllers\Api\Portal\InvoiceController::class, 'index']);
        Route::get('invoices/{id}', [\App\Http\Controllers\Api\Portal\InvoiceController::class, 'show']);
        Route::get('invoices/{id}/pdf', [\App\Http\Controllers\Api\Portal\InvoiceController::class, 'pdf']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [\App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::put('/user', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::put('/user/password', [\App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/user/2fa', [\App\Http\Controllers\Api\TwoFactorController::class, 'status']);
    Route::post('/user/2fa/enable', [\App\Http\Controllers\Api\TwoFactorController::class, 'enable']);
    Route::post('/user/2fa/confirm', [\App\Http\Controllers\Api\TwoFactorController::class, 'confirm']);
    Route::post('/user/2fa/verify', [\App\Http\Controllers\Api\TwoFactorController::class, 'verify']);
    Route::post('/user/2fa/disable', [\App\Http\Controllers\Api\TwoFactorController::class, 'disable']);
    Route::post('/email/verification-notification', [\App\Http\Controllers\Api\EmailVerificationController::class, 'send']);
});

// API v1 - company-scoped (middleware applies company context), 60 requests per minute
Route::prefix('v1')->middleware(['auth:sanctum', 'company', 'throttle:60,1'])->group(function () {
    Route::apiResource('companies', \App\Http\Controllers\Api\CompanyController::class)->only(['index', 'show', 'store', 'update']);
    Route::put('companies/{company}/my-role', [\App\Http\Controllers\Api\CompanyController::class, 'updateMyRole']);
    Route::get('companies/{company}/members', [\App\Http\Controllers\Api\CompanyController::class, 'members']);
    Route::put('companies/{company}/members/{userId}', [\App\Http\Controllers\Api\CompanyController::class, 'updateMemberRole']);
    Route::get('accounts/{account}/balance', [\App\Http\Controllers\Api\ChartOfAccountController::class, 'balance']);
    Route::apiResource('accounts', \App\Http\Controllers\Api\ChartOfAccountController::class);
    Route::apiResource('journal-entries', \App\Http\Controllers\Api\JournalEntryController::class);
    Route::post('journal-entries/{journal_entry}/post', [\App\Http\Controllers\Api\JournalEntryController::class, 'post']);
    Route::get('reports/profit-loss', [\App\Http\Controllers\Api\ReportController::class, 'profitLoss']);
    Route::get('reports/profit-loss/pdf', [\App\Http\Controllers\Api\ReportController::class, 'profitLossPdf']);
    Route::get('reports/balance-sheet', [\App\Http\Controllers\Api\ReportController::class, 'balanceSheet']);
    Route::get('reports/balance-sheet/pdf', [\App\Http\Controllers\Api\ReportController::class, 'balanceSheetPdf']);
    Route::get('reports/trial-balance', [\App\Http\Controllers\Api\ReportController::class, 'trialBalance']);
    Route::get('reports/trial-balance/pdf', [\App\Http\Controllers\Api\ReportController::class, 'trialBalancePdf']);
    Route::get('reports/gl-detail', [\App\Http\Controllers\Api\ReportController::class, 'glDetail']);
    Route::get('reports/gl-detail/pdf', [\App\Http\Controllers\Api\ReportController::class, 'glDetailPdf']);
    Route::get('reports/ar-aging', [\App\Http\Controllers\Api\ReportController::class, 'arAging']);
    Route::get('reports/ar-aging/pdf', [\App\Http\Controllers\Api\ReportController::class, 'arAgingPdf']);
    Route::get('reports/ap-aging', [\App\Http\Controllers\Api\ReportController::class, 'apAging']);
    Route::get('reports/ap-aging/pdf', [\App\Http\Controllers\Api\ReportController::class, 'apAgingPdf']);
    Route::get('reports/cash-flow', [\App\Http\Controllers\Api\ReportController::class, 'cashFlow']);
    Route::get('reports/cash-flow/pdf', [\App\Http\Controllers\Api\ReportController::class, 'cashFlowPdf']);
    Route::get('reports/tax-summary', [\App\Http\Controllers\Api\ReportController::class, 'taxSummary']);
    Route::get('reports/tax-summary/pdf', [\App\Http\Controllers\Api\ReportController::class, 'taxSummaryPdf']);
    Route::get('reports/customer-statement', [\App\Http\Controllers\Api\ReportController::class, 'customerStatement']);
    Route::get('reports/customer-statement/pdf', [\App\Http\Controllers\Api\ReportController::class, 'customerStatementPdf']);
    Route::get('reports/vendor-statement', [\App\Http\Controllers\Api\ReportController::class, 'vendorStatement']);
    Route::get('reports/vendor-statement/pdf', [\App\Http\Controllers\Api\ReportController::class, 'vendorStatementPdf']);
    Route::post('invoices/{invoice}/duplicate', [\App\Http\Controllers\Api\InvoiceController::class, 'duplicate']);
    Route::post('bills/{bill}/duplicate', [\App\Http\Controllers\Api\BillController::class, 'duplicate']);
    Route::apiResource('customers', \App\Http\Controllers\Api\CustomerController::class);
    Route::apiResource('vendors', \App\Http\Controllers\Api\VendorController::class);
    Route::apiResource('invoices', \App\Http\Controllers\Api\InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [\App\Http\Controllers\Api\InvoiceController::class, 'pdf']);
    Route::post('invoices/{invoice}/payment-link', [\App\Http\Controllers\Api\InvoiceController::class, 'paymentLink']);
    Route::post('invoices/{invoice}/payments', [\App\Http\Controllers\Api\InvoiceController::class, 'recordPayment']);
    Route::apiResource('bills', \App\Http\Controllers\Api\BillController::class);
    Route::post('bills/{bill}/payments', [\App\Http\Controllers\Api\BillController::class, 'recordPayment']);
    Route::apiResource('credit-notes', \App\Http\Controllers\Api\CreditNoteController::class)->only(['index', 'store', 'show']);
    Route::post('credit-notes/{credit_note}/apply', [\App\Http\Controllers\Api\CreditNoteController::class, 'apply']);
    Route::get('period-closing', [\App\Http\Controllers\Api\PeriodClosingController::class, 'index']);
    Route::post('period-closing/close', [\App\Http\Controllers\Api\PeriodClosingController::class, 'close']);
    Route::get('period-closing/check', [\App\Http\Controllers\Api\PeriodClosingController::class, 'isClosed']);
    Route::apiResource('items', \App\Http\Controllers\Api\ItemController::class);
    Route::apiResource('tax-rates', \App\Http\Controllers\Api\TaxRateController::class);
    Route::apiResource('bank-accounts', \App\Http\Controllers\Api\BankAccountController::class);
    Route::apiResource('bank-transactions', \App\Http\Controllers\Api\BankTransactionController::class);
    Route::get('dashboard/summary', [\App\Http\Controllers\Api\DashboardController::class, 'summary']);
    Route::get('dashboard/operational', [\App\Http\Controllers\Api\DashboardController::class, 'operational']);
    Route::get('modules', [\App\Http\Controllers\Api\ModuleController::class, 'index']);
    Route::patch('modules/{alias}', [\App\Http\Controllers\Api\ModuleController::class, 'update']);
    Route::get('permissions', [\App\Http\Controllers\Api\PermissionController::class, 'index']);
    Route::get('permissions/definitions', [\App\Http\Controllers\Api\PermissionController::class, 'definitions']);
    Route::apiResource('roles', \App\Http\Controllers\Api\RoleController::class);
    Route::apiResource('webhooks', \App\Http\Controllers\Api\WebhookController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('recurring-invoices', \App\Http\Controllers\Api\RecurringInvoiceController::class);
    Route::apiResource('recurring-bills', \App\Http\Controllers\Api\RecurringBillController::class);
    Route::get('audit-logs', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
    Route::get('communications', [\App\Http\Controllers\Api\CommunicationController::class, 'index']);
    Route::post('communications', [\App\Http\Controllers\Api\CommunicationController::class, 'store']);
    Route::get('communications/{communication}', [\App\Http\Controllers\Api\CommunicationController::class, 'show']);
    Route::put('communications/{communication}', [\App\Http\Controllers\Api\CommunicationController::class, 'update']);
    Route::delete('communications/{communication}', [\App\Http\Controllers\Api\CommunicationController::class, 'destroy']);
    Route::apiResource('meetings', \App\Http\Controllers\Api\MeetingController::class);
    Route::get('notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::put('notifications/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
    Route::post('notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);
    Route::get('investments/summary', [\App\Http\Controllers\Api\InvestmentController::class, 'summary']);
    Route::apiResource('investments', \App\Http\Controllers\Api\InvestmentController::class);
    Route::get('investments/{investment}/transactions', [\App\Http\Controllers\Api\InvestmentTransactionController::class, 'index']);
    Route::post('investments/{investment}/transactions', [\App\Http\Controllers\Api\InvestmentTransactionController::class, 'store']);
    Route::delete('investments/{investment}/transactions/{transaction}', [\App\Http\Controllers\Api\InvestmentTransactionController::class, 'destroy']);
    // Employee dashboard (availability, shifts, chat)
    Route::get('employee/dashboard', [\App\Http\Controllers\Api\EmployeeController::class, 'dashboard']);
    Route::get('employee/manager', [\App\Http\Controllers\Api\EmployeeController::class, 'manager']);
    Route::get('employee/colleagues', [\App\Http\Controllers\Api\EmployeeController::class, 'colleagues']);
    Route::get('employee/announcements', [\App\Http\Controllers\Api\EmployeeController::class, 'announcements']);
    Route::get('employee/availabilities', [\App\Http\Controllers\Api\EmployeeAvailabilityController::class, 'index']);
    Route::post('employee/availabilities', [\App\Http\Controllers\Api\EmployeeAvailabilityController::class, 'store']);
    Route::delete('employee/availabilities/{employee_availability}', [\App\Http\Controllers\Api\EmployeeAvailabilityController::class, 'destroy']);
    Route::get('employee/shifts', [\App\Http\Controllers\Api\EmployeeShiftController::class, 'index']);
    Route::post('employee/shifts', [\App\Http\Controllers\Api\EmployeeShiftController::class, 'store']);
    Route::put('employee/shifts/{shift}', [\App\Http\Controllers\Api\EmployeeShiftController::class, 'update']);
    Route::delete('employee/shifts/{shift}', [\App\Http\Controllers\Api\EmployeeShiftController::class, 'destroy']);
    Route::get('employee/messages', [\App\Http\Controllers\Api\EmployeeMessageController::class, 'index']);
    Route::post('employee/messages', [\App\Http\Controllers\Api\EmployeeMessageController::class, 'store']);
    Route::put('employee/messages/{employee_message}/read', [\App\Http\Controllers\Api\EmployeeMessageController::class, 'markRead']);
});

// Set current company (for multi-tenant)
Route::middleware('auth:sanctum')->post('/v1/company/switch', [\App\Http\Controllers\Api\CompanyController::class, 'switchCompany']);
