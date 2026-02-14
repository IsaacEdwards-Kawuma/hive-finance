<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecurringInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $list = RecurringInvoice::with('customer')->orderBy('next_run_date')->paginate($request->get('per_page', 20));
        return response()->json($list);
    }

    public function show(RecurringInvoice $recurringInvoice): JsonResponse
    {
        $recurringInvoice->load('customer');
        return response()->json(['data' => $recurringInvoice]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'template' => 'nullable|array',
            'enabled' => 'boolean',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['next_run_date'] = $validated['start_date'];
        $validated['enabled'] = $validated['enabled'] ?? true;
        $recurring = RecurringInvoice::create($validated);
        $recurring->load('customer');
        return response()->json(['data' => $recurring], 201);
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice): JsonResponse
    {
        $validated = $request->validate([
            'frequency' => 'sometimes|in:daily,weekly,monthly,yearly',
            'next_run_date' => 'sometimes|date',
            'end_date' => 'nullable|date',
            'template' => 'nullable|array',
            'enabled' => 'boolean',
        ]);
        $recurringInvoice->update($validated);
        $recurringInvoice->load('customer');
        return response()->json(['data' => $recurringInvoice]);
    }

    public function destroy(RecurringInvoice $recurringInvoice): JsonResponse
    {
        $recurringInvoice->delete();
        return response()->json(null, 204);
    }
}
