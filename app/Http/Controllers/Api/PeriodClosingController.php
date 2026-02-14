<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClosedPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeriodClosingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $periods = ClosedPeriod::orderByDesc('period_end')->paginate($request->get('per_page', 12));
        return response()->json($periods);
    }

    public function close(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);
        $companyId = session('current_company_id');
        $exists = ClosedPeriod::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('period_start', $validated['period_start'])
            ->where('period_end', $validated['period_end'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Period already closed'], 422);
        }
        $period = ClosedPeriod::withoutGlobalScope(\App\Scopes\CompanyScope::class)->create([
            'company_id' => $companyId,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'closed_at' => now(),
            'closed_by' => $request->user()?->id,
        ]);
        return response()->json(['data' => $period], 201);
    }

    public function isClosed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);
        $companyId = session('current_company_id');
        $closed = ClosedPeriod::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $companyId)
            ->where('period_start', '<=', $validated['date'])
            ->where('period_end', '>=', $validated['date'])
            ->exists();
        return response()->json(['closed' => $closed]);
    }
}
