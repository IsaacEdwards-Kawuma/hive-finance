<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\Shift;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $companyId = session('current_company_id');
        $from = request()->get('from');
        $to = request()->get('to');
        $invoiceQuery = Invoice::whereIn('status', ['sent', 'partial', 'paid']);
        $arQuery = Invoice::whereIn('status', ['sent', 'partial']);
        $apQuery = Bill::whereIn('status', ['received', 'partial']);
        if ($from) {
            $invoiceQuery->whereDate('invoice_date', '>=', $from);
            $arQuery->whereDate('invoice_date', '>=', $from);
        }
        if ($to) {
            $invoiceQuery->whereDate('invoice_date', '<=', $to);
            $arQuery->whereDate('invoice_date', '<=', $to);
        }
        if ($from) {
            $apQuery->whereDate('bill_date', '>=', $from);
        }
        if ($to) {
            $apQuery->whereDate('bill_date', '<=', $to);
        }
        $revenue = (float) $invoiceQuery->sum('total');
        $outstandingAr = (float) $arQuery->get()->sum('balance_due');
        $outstandingAp = (float) $apQuery->get()->sum('balance_due');
        $recentInvoices = Invoice::with('customer')->when($from, fn ($q) => $q->whereDate('invoice_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('invoice_date', '<=', $to))->orderByDesc('invoice_date')->limit(5)->get();
        $recentBills = Bill::with('vendor')->when($from, fn ($q) => $q->whereDate('bill_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('bill_date', '<=', $to))->orderByDesc('bill_date')->limit(5)->get();
        return response()->json([
            'data' => [
                'revenue_ytd' => (float) $revenue,
                'outstanding_ar' => (float) $outstandingAr,
                'outstanding_ap' => (float) $outstandingAp,
                'recent_invoices' => $recentInvoices,
                'recent_bills' => $recentBills,
            ],
        ]);
    }

    /** Operational summary: shifts and team size. For management and operational dashboards. */
    public function operational(): JsonResponse
    {
        $companyId = session('current_company_id');
        $now = now();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $shiftsWeek = Shift::whereBetween('start_at', [$weekStart, $weekEnd])->count();
        $shiftsMonth = Shift::whereBetween('start_at', [$monthStart, $monthEnd])->count();
        $company = Company::find($companyId);
        $teamMembersCount = $company ? $company->users()->count() : 0;
        $upcomingShifts = Shift::with('user:id,name')
            ->where('start_at', '>=', $now)
            ->orderBy('start_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'shifts_this_week' => $shiftsWeek,
                'shifts_this_month' => $shiftsMonth,
                'team_members_count' => $teamMembersCount,
                'upcoming_shifts' => $upcomingShifts,
            ],
        ]);
    }
}
