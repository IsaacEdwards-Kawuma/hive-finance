<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        $invoices = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $customer->company_id)
            ->where('customer_id', $customer->id)
            ->with('items')
            ->orderByDesc('invoice_date')
            ->paginate($request->get('per_page', 15));
        return response()->json($invoices);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user();
        $invoice = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $customer->company_id)
            ->where('customer_id', $customer->id)
            ->with('items')
            ->findOrFail($id);
        return response()->json(['data' => $invoice]);
    }

    public function pdf(Request $request, int $id)
    {
        /** @var Customer $customer */
        $customer = $request->user();
        $invoice = Invoice::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $customer->company_id)
            ->where('customer_id', $customer->id)
            ->with('customer', 'items')
            ->findOrFail($id);
        $company = \App\Models\Company::find($customer->company_id);
        return response()->view('invoice-pdf', ['invoice' => $invoice, 'company' => $company])
            ->header('Content-Type', 'text/html');
    }
}
