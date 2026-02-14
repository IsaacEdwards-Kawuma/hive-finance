<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecurringBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringBillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $list = RecurringBill::with('vendor')->orderBy('next_run_date')->paginate($request->get('per_page', 20));
        return response()->json($list);
    }

    public function show(RecurringBill $recurringBill): JsonResponse
    {
        $recurringBill->load('vendor');
        return response()->json(['data' => $recurringBill]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'template' => 'nullable|array',
            'enabled' => 'boolean',
        ]);
        $validated['company_id'] = session('current_company_id');
        $validated['next_run_date'] = $validated['start_date'];
        $validated['enabled'] = $validated['enabled'] ?? true;
        $recurring = RecurringBill::create($validated);
        $recurring->load('vendor');
        return response()->json(['data' => $recurring], 201);
    }

    public function update(Request $request, RecurringBill $recurringBill): JsonResponse
    {
        $validated = $request->validate([
            'frequency' => 'sometimes|in:daily,weekly,monthly,yearly',
            'next_run_date' => 'sometimes|date',
            'end_date' => 'nullable|date',
            'template' => 'nullable|array',
            'enabled' => 'boolean',
        ]);
        $recurringBill->update($validated);
        $recurringBill->load('vendor');
        return response()->json(['data' => $recurringBill]);
    }

    public function destroy(RecurringBill $recurringBill): JsonResponse
    {
        $recurringBill->delete();
        return response()->json(null, 204);
    }
}
