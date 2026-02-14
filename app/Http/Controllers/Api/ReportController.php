<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Invoice;
use App\Models\Bill;
use App\Models\JournalEntryLine;
use App\Models\BankTransaction;
use App\Models\Payment;
use App\Models\TaxRate;
use App\Models\InvoiceItem;
use App\Models\BillItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function profitLoss(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $income = $this->accountBalancesByType($companyId, ['income'], $from, $to);
        $expense = $this->accountBalancesByType($companyId, ['expense'], $from, $to);
        $totalIncome = $income->sum('balance');
        $totalExpense = $expense->sum('balance');
        return response()->json([
            'data' => [
                'from' => $from,
                'to' => $to,
                'income' => $income,
                'expense' => $expense,
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_income' => $totalIncome - $totalExpense,
            ],
        ]);
    }

    public function balanceSheet(Request $request): JsonResponse
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $assets = $this->accountBalancesByType($companyId, ['asset'], null, $asOf);
        $liabilities = $this->accountBalancesByType($companyId, ['liability'], null, $asOf);
        $equity = $this->accountBalancesByType($companyId, ['equity'], null, $asOf);
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');
        return response()->json([
            'data' => [
                'as_of' => $asOf,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
            ],
        ]);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $balances = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.date', '<=', $asOf)
            ->select('journal_entry_lines.account_id', DB::raw('SUM(journal_entry_lines.debit) as debit'), DB::raw('SUM(journal_entry_lines.credit) as credit'))
            ->groupBy('journal_entry_lines.account_id')
            ->get();
        $accounts = Account::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->get()->keyBy('id');
        $rows = $balances->map(function ($row) use ($accounts) {
            $account = $accounts->get($row->account_id);
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            return [
                'account_id' => $row->account_id,
                'code' => $account?->code,
                'name' => $account?->name,
                'debit' => $debit,
                'credit' => $credit,
            ];
        });
        return response()->json(['data' => ['as_of' => $asOf, 'rows' => $rows]]);
    }

    public function glDetail(Request $request): JsonResponse
    {
        $accountId = $request->get('account_id');
        $from = $request->get('from');
        $to = $request->get('to');
        if (!$accountId) {
            return response()->json(['message' => 'account_id required'], 422);
        }
        $query = JournalEntryLine::with('journalEntry')
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'));
        if ($from) {
            $query->whereHas('journalEntry', fn ($q) => $q->whereDate('date', '>=', $from));
        }
        if ($to) {
            $query->whereHas('journalEntry', fn ($q) => $q->whereDate('date', '<=', $to));
        }
        $lines = $query->orderBy('journal_entry_id')->get();
        return response()->json(['data' => $lines]);
    }

    public function arAging(Request $request): JsonResponse
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $invoices = Invoice::with('customer')
            ->whereIn('status', ['sent', 'partial'])
            ->whereDate('due_date', '<=', $asOf)
            ->get();
        $buckets = ['current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $byCustomer = [];
        foreach ($invoices as $inv) {
            $balance = $inv->balance_due;
            if ($balance <= 0) {
                continue;
            }
            $days = now()->parse($inv->due_date)->diffInDays($asOf, false);
            if ($days <= 0) {
                $bucket = 'current';
            } elseif ($days <= 30) {
                $bucket = '1-30';
            } elseif ($days <= 60) {
                $bucket = '31-60';
            } elseif ($days <= 90) {
                $bucket = '61-90';
            } else {
                $bucket = '90+';
            }
            $buckets[$bucket] = ($buckets[$bucket] ?? 0) + $balance;
            $cid = $inv->customer_id;
            if (!isset($byCustomer[$cid])) {
                $byCustomer[$cid] = ['customer' => $inv->customer->name, 'invoices' => [], 'total' => 0];
            }
            $byCustomer[$cid]['invoices'][] = ['number' => $inv->invoice_number, 'due_date' => $inv->due_date->format('Y-m-d'), 'balance' => $balance];
            $byCustomer[$cid]['total'] += $balance;
        }
        return response()->json(['data' => ['as_of' => $asOf, 'buckets' => $buckets, 'by_customer' => array_values($byCustomer)]]);
    }

    public function apAging(Request $request): JsonResponse
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $bills = Bill::with('vendor')
            ->whereIn('status', ['received', 'partial'])
            ->whereDate('due_date', '<=', $asOf)
            ->get();
        $buckets = ['current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $byVendor = [];
        foreach ($bills as $bill) {
            $balance = $bill->balance_due;
            if ($balance <= 0) {
                continue;
            }
            $days = now()->parse($bill->due_date)->diffInDays($asOf, false);
            if ($days <= 0) {
                $bucket = 'current';
            } elseif ($days <= 30) {
                $bucket = '1-30';
            } elseif ($days <= 60) {
                $bucket = '31-60';
            } elseif ($days <= 90) {
                $bucket = '61-90';
            } else {
                $bucket = '90+';
            }
            $buckets[$bucket] = ($buckets[$bucket] ?? 0) + $balance;
            $vid = $bill->vendor_id;
            if (!isset($byVendor[$vid])) {
                $byVendor[$vid] = ['vendor' => $bill->vendor->name, 'bills' => [], 'total' => 0];
            }
            $byVendor[$vid]['bills'][] = ['number' => $bill->bill_number, 'due_date' => $bill->due_date->format('Y-m-d'), 'balance' => $balance];
            $byVendor[$vid]['total'] += $balance;
        }
        return response()->json(['data' => ['as_of' => $asOf, 'buckets' => $buckets, 'by_vendor' => array_values($byVendor)]]);
    }

    public function cashFlow(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $operating = $this->accountBalancesByType($companyId, ['income', 'expense'], $from, $to);
        $incomeTotal = $operating->whereIn('type', ['income'])->sum('balance');
        $expenseTotal = $operating->whereIn('type', ['expense'])->sum('balance');
        $netOperating = $incomeTotal - abs($expenseTotal);
        $bankTx = BankTransaction::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('SUM(amount) as total')
            ->value('total');
        $bankNet = (float) ($bankTx ?? 0);
        return response()->json([
            'data' => [
                'from' => $from,
                'to' => $to,
                'operating_activities' => ['income' => $incomeTotal, 'expense' => abs($expenseTotal), 'net' => $netOperating],
                'bank_net_change' => $bankNet,
                'net_cash_change' => $bankNet,
            ],
        ]);
    }

    public function taxSummary(Request $request): JsonResponse
    {
        $from = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $rates = TaxRate::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->get();
        $salesTax = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['sent', 'partial', 'paid'])
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(invoice_items.tax), 0) as total_tax')
            ->value('total_tax');
        $purchaseTax = BillItem::query()
            ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
            ->where('bills.company_id', $companyId)
            ->whereIn('bills.status', ['received', 'partial', 'paid'])
            ->whereBetween('bills.bill_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(bill_items.tax), 0) as total_tax')
            ->value('total_tax');
        return response()->json([
            'data' => [
                'from' => $from,
                'to' => $to,
                'sales_tax_collected' => (float) ($salesTax ?? 0),
                'purchase_tax_paid' => (float) ($purchaseTax ?? 0),
                'tax_rates' => $rates->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'rate' => $r->rate]),
            ],
        ]);
    }

    public function customerStatement(Request $request): JsonResponse
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);
        $from = $request->get('from', now()->subYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $invoices = Invoice::with('payments')->where('customer_id', $request->customer_id)
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date')
            ->get();
        $lines = [];
        foreach ($invoices as $inv) {
            $lines[] = ['type' => 'invoice', 'date' => $inv->invoice_date->format('Y-m-d'), 'number' => $inv->invoice_number, 'amount' => $inv->total, 'balance' => $inv->balance_due ?? $inv->total];
            foreach ($inv->payments ?? [] as $pay) {
                $lines[] = ['type' => 'payment', 'date' => $pay->paid_at->format('Y-m-d'), 'number' => $pay->reference ?? 'Payment', 'amount' => -$pay->amount, 'balance' => null];
            }
        }
        return response()->json(['data' => ['from' => $from, 'to' => $to, 'lines' => $lines]]);
    }

    public function vendorStatement(Request $request): JsonResponse
    {
        $request->validate(['vendor_id' => 'required|exists:vendors,id']);
        $from = $request->get('from', now()->subYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $bills = Bill::with('payments')->where('vendor_id', $request->vendor_id)
            ->whereBetween('bill_date', [$from, $to])
            ->orderBy('bill_date')
            ->get();
        $lines = [];
        foreach ($bills as $bill) {
            $lines[] = ['type' => 'bill', 'date' => $bill->bill_date->format('Y-m-d'), 'number' => $bill->bill_number, 'amount' => $bill->total, 'balance' => $bill->balance_due ?? $bill->total];
            foreach ($bill->payments ?? [] as $pay) {
                $lines[] = ['type' => 'payment', 'date' => $pay->paid_at->format('Y-m-d'), 'number' => $pay->reference ?? 'Payment', 'amount' => -$pay->amount, 'balance' => null];
            }
        }
        return response()->json(['data' => ['from' => $from, 'to' => $to, 'lines' => $lines]]);
    }

    public function profitLossPdf(Request $request)
    {
        $from = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $income = $this->accountBalancesByType($companyId, ['income'], $from, $to);
        $expense = $this->accountBalancesByType($companyId, ['expense'], $from, $to);
        $totalIncome = $income->sum('balance');
        $totalExpense = $expense->sum('balance');
        $company = Company::find($companyId);
        $pdf = Pdf::loadView('reports.pdf-profit-loss', [
            'company' => $company,
            'reportTitle' => 'Profit & Loss',
            'period' => "From {$from} to {$to}",
            'income' => $income,
            'expense' => $expense,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_income' => $totalIncome - $totalExpense,
        ]);
        return $pdf->download('profit-loss-' . $from . '-' . $to . '.pdf');
    }

    public function balanceSheetPdf(Request $request)
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $assets = $this->accountBalancesByType($companyId, ['asset'], null, $asOf);
        $liabilities = $this->accountBalancesByType($companyId, ['liability'], null, $asOf);
        $equity = $this->accountBalancesByType($companyId, ['equity'], null, $asOf);
        $company = Company::find($companyId);
        $pdf = Pdf::loadView('reports.pdf-balance-sheet', [
            'company' => $company,
            'reportTitle' => 'Balance Sheet',
            'period' => "As of {$asOf}",
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => $assets->sum('balance'),
            'total_liabilities' => $liabilities->sum('balance'),
            'total_equity' => $equity->sum('balance'),
        ]);
        return $pdf->download('balance-sheet-' . $asOf . '.pdf');
    }

    public function trialBalancePdf(Request $request)
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $balances = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.date', '<=', $asOf)
            ->select('journal_entry_lines.account_id', DB::raw('SUM(journal_entry_lines.debit) as debit'), DB::raw('SUM(journal_entry_lines.credit) as credit'))
            ->groupBy('journal_entry_lines.account_id')
            ->get();
        $accounts = Account::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->get()->keyBy('id');
        $rows = $balances->map(function ($row) use ($accounts) {
            $account = $accounts->get($row->account_id);
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            return [
                'account_id' => $row->account_id,
                'code' => $account?->code,
                'name' => $account?->name,
                'debit' => $debit,
                'credit' => $credit,
            ];
        });
        $company = Company::find($companyId);
        $pdf = Pdf::loadView('reports.pdf-trial-balance', [
            'company' => $company,
            'reportTitle' => 'Trial Balance',
            'period' => "As of {$asOf}",
            'rows' => $rows,
        ]);
        return $pdf->download('trial-balance-' . $asOf . '.pdf');
    }

    public function arAgingPdf(Request $request)
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $invoices = Invoice::with('customer')
            ->whereIn('status', ['sent', 'partial'])
            ->whereDate('due_date', '<=', $asOf)
            ->get();
        $buckets = ['current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $byCustomer = [];
        foreach ($invoices as $inv) {
            $balance = $inv->balance_due;
            if ($balance <= 0) continue;
            $days = now()->parse($inv->due_date)->diffInDays($asOf, false);
            $bucket = $days <= 0 ? 'current' : ($days <= 30 ? '1-30' : ($days <= 60 ? '31-60' : ($days <= 90 ? '61-90' : '90+')));
            $buckets[$bucket] = ($buckets[$bucket] ?? 0) + $balance;
            $cid = $inv->customer_id;
            if (!isset($byCustomer[$cid])) {
                $byCustomer[$cid] = ['customer' => $inv->customer->name ?? 'Unknown', 'invoices' => [], 'total' => 0];
            }
            $byCustomer[$cid]['invoices'][] = ['number' => $inv->invoice_number, 'due_date' => $inv->due_date->format('Y-m-d'), 'balance' => $balance];
            $byCustomer[$cid]['total'] += $balance;
        }
        $company = Company::find(session('current_company_id'));
        $pdf = Pdf::loadView('reports.pdf-ar-aging', [
            'company' => $company,
            'reportTitle' => 'Accounts Receivable Aging',
            'period' => "As of {$asOf}",
            'buckets' => $buckets,
            'by_customer' => array_values($byCustomer),
        ]);
        return $pdf->download('ar-aging-' . $asOf . '.pdf');
    }

    public function apAgingPdf(Request $request)
    {
        $asOf = $request->get('as_of', now()->format('Y-m-d'));
        $bills = Bill::with('vendor')
            ->whereIn('status', ['received', 'partial'])
            ->whereDate('due_date', '<=', $asOf)
            ->get();
        $buckets = ['current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        $byVendor = [];
        foreach ($bills as $bill) {
            $balance = $bill->balance_due;
            if ($balance <= 0) continue;
            $days = now()->parse($bill->due_date)->diffInDays($asOf, false);
            $bucket = $days <= 0 ? 'current' : ($days <= 30 ? '1-30' : ($days <= 60 ? '31-60' : ($days <= 90 ? '61-90' : '90+')));
            $buckets[$bucket] = ($buckets[$bucket] ?? 0) + $balance;
            $vid = $bill->vendor_id;
            if (!isset($byVendor[$vid])) {
                $byVendor[$vid] = ['vendor' => $bill->vendor->name ?? 'Unknown', 'bills' => [], 'total' => 0];
            }
            $byVendor[$vid]['bills'][] = ['number' => $bill->bill_number, 'due_date' => $bill->due_date->format('Y-m-d'), 'balance' => $balance];
            $byVendor[$vid]['total'] += $balance;
        }
        $company = Company::find(session('current_company_id'));
        $pdf = Pdf::loadView('reports.pdf-ap-aging', [
            'company' => $company,
            'reportTitle' => 'Accounts Payable Aging',
            'period' => "As of {$asOf}",
            'buckets' => $buckets,
            'by_vendor' => array_values($byVendor),
        ]);
        return $pdf->download('ap-aging-' . $asOf . '.pdf');
    }

    public function cashFlowPdf(Request $request)
    {
        $from = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $operating = $this->accountBalancesByType($companyId, ['income', 'expense'], $from, $to);
        $incomeTotal = $operating->whereIn('type', ['income'])->sum('balance');
        $expenseTotal = $operating->whereIn('type', ['expense'])->sum('balance');
        $netOperating = $incomeTotal - abs($expenseTotal);
        $bankNet = (float) (BankTransaction::withoutGlobalScope(\App\Scopes\CompanyScope::class)
            ->where('company_id', $companyId)
            ->whereBetween('date', [$from, $to])
            ->selectRaw('SUM(amount) as total')
            ->value('total') ?? 0);
        $company = Company::find($companyId);
        $pdf = Pdf::loadView('reports.pdf-cash-flow', [
            'company' => $company,
            'reportTitle' => 'Cash Flow',
            'period' => "From {$from} to {$to}",
            'operating_activities' => ['income' => $incomeTotal, 'expense' => abs($expenseTotal), 'net' => $netOperating],
            'bank_net_change' => $bankNet,
            'net_cash_change' => $bankNet,
        ]);
        return $pdf->download('cash-flow-' . $from . '-' . $to . '.pdf');
    }

    public function taxSummaryPdf(Request $request)
    {
        $from = $request->get('from', now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $companyId = session('current_company_id');
        $rates = TaxRate::withoutGlobalScope(\App\Scopes\CompanyScope::class)->where('company_id', $companyId)->get();
        $salesTax = (float) (InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.company_id', $companyId)
            ->whereIn('invoices.status', ['sent', 'partial', 'paid'])
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(invoice_items.tax), 0) as total_tax')
            ->value('total_tax') ?? 0);
        $purchaseTax = (float) (BillItem::query()
            ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
            ->where('bills.company_id', $companyId)
            ->whereIn('bills.status', ['received', 'partial', 'paid'])
            ->whereBetween('bills.bill_date', [$from, $to])
            ->selectRaw('COALESCE(SUM(bill_items.tax), 0) as total_tax')
            ->value('total_tax') ?? 0);
        $company = Company::find($companyId);
        $pdf = Pdf::loadView('reports.pdf-tax-summary', [
            'company' => $company,
            'reportTitle' => 'Tax Summary',
            'period' => "From {$from} to {$to}",
            'sales_tax_collected' => $salesTax,
            'purchase_tax_paid' => $purchaseTax,
            'tax_rates' => $rates,
        ]);
        return $pdf->download('tax-summary-' . $from . '-' . $to . '.pdf');
    }

    public function customerStatementPdf(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:customers,id']);
        $from = $request->get('from', now()->subYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $customer = Customer::find($request->customer_id);
        $invoices = Invoice::with('payments')->where('customer_id', $request->customer_id)
            ->whereBetween('invoice_date', [$from, $to])
            ->orderBy('invoice_date')
            ->get();
        $lines = [];
        foreach ($invoices as $inv) {
            $lines[] = ['type' => 'invoice', 'date' => $inv->invoice_date->format('Y-m-d'), 'number' => $inv->invoice_number, 'amount' => $inv->total, 'balance' => $inv->balance_due ?? $inv->total];
            foreach ($inv->payments ?? [] as $pay) {
                $lines[] = ['type' => 'payment', 'date' => $pay->paid_at->format('Y-m-d'), 'number' => $pay->reference ?? 'Payment', 'amount' => -$pay->amount, 'balance' => null];
            }
        }
        $company = Company::find(session('current_company_id'));
        $pdf = Pdf::loadView('reports.pdf-customer-statement', [
            'company' => $company,
            'reportTitle' => 'Customer Statement',
            'period' => "From {$from} to {$to}",
            'customer' => $customer,
            'lines' => $lines,
        ]);
        $name = \Illuminate\Support\Str::slug($customer->name ?? 'customer');
        return $pdf->download("customer-statement-{$name}-{$from}-{$to}.pdf");
    }

    public function vendorStatementPdf(Request $request)
    {
        $request->validate(['vendor_id' => 'required|exists:vendors,id']);
        $from = $request->get('from', now()->subYear()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $vendor = Vendor::find($request->vendor_id);
        $bills = Bill::with('payments')->where('vendor_id', $request->vendor_id)
            ->whereBetween('bill_date', [$from, $to])
            ->orderBy('bill_date')
            ->get();
        $lines = [];
        foreach ($bills as $bill) {
            $lines[] = ['type' => 'bill', 'date' => $bill->bill_date->format('Y-m-d'), 'number' => $bill->bill_number, 'amount' => $bill->total, 'balance' => $bill->balance_due ?? $bill->total];
            foreach ($bill->payments ?? [] as $pay) {
                $lines[] = ['type' => 'payment', 'date' => $pay->paid_at->format('Y-m-d'), 'number' => $pay->reference ?? 'Payment', 'amount' => -$pay->amount, 'balance' => null];
            }
        }
        $company = Company::find(session('current_company_id'));
        $pdf = Pdf::loadView('reports.pdf-vendor-statement', [
            'company' => $company,
            'reportTitle' => 'Vendor Statement',
            'period' => "From {$from} to {$to}",
            'vendor' => $vendor,
            'lines' => $lines,
        ]);
        $name = \Illuminate\Support\Str::slug($vendor->name ?? 'vendor');
        return $pdf->download("vendor-statement-{$name}-{$from}-{$to}.pdf");
    }

    public function glDetailPdf(Request $request)
    {
        $accountId = $request->get('account_id');
        $from = $request->get('from');
        $to = $request->get('to');
        if (!$accountId) {
            return response()->json(['message' => 'account_id required'], 422);
        }
        $account = Account::withoutGlobalScope(\App\Scopes\CompanyScope::class)->find($accountId);
        $query = JournalEntryLine::with('journalEntry')
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'));
        if ($from) {
            $query->whereHas('journalEntry', fn ($q) => $q->whereDate('date', '>=', $from));
        }
        if ($to) {
            $query->whereHas('journalEntry', fn ($q) => $q->whereDate('date', '<=', $to));
        }
        $lines = $query->orderBy('journal_entry_id')->get();
        $company = Company::find(session('current_company_id'));
        $period = $from && $to ? "From {$from} to {$to}" : ($from ? "From {$from}" : ($to ? "Through {$to}" : 'All time'));
        $pdf = Pdf::loadView('reports.pdf-gl-detail', [
            'company' => $company,
            'reportTitle' => 'GL Detail' . ($account ? ' – ' . $account->code . ' ' . $account->name : ''),
            'period' => $period,
            'account' => $account,
            'lines' => $lines,
        ]);
        $filename = 'gl-detail-' . ($account ? \Illuminate\Support\Str::slug($account->code) : $accountId);
        if ($from) $filename .= '-' . $from;
        if ($to) $filename .= '-' . $to;
        return $pdf->download($filename . '.pdf');
    }

    private function accountBalancesByType($companyId, array $types, ?string $from, ?string $to)
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', 'posted')
            ->whereIn('accounts.type', $types)
            ->select('journal_entry_lines.account_id', 'accounts.code', 'accounts.name', 'accounts.type', DB::raw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) as balance'));
        if ($from) {
            $query->whereDate('journal_entries.date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('journal_entries.date', '<=', $to);
        }
        $result = $query->groupBy('journal_entry_lines.account_id', 'accounts.code', 'accounts.name', 'accounts.type')->get();
        foreach ($result as $row) {
            $row->balance = (float) $row->balance;
            if (in_array($row->type ?? null, ['income'], true)) {
                $row->balance = abs($row->balance);
            }
        }
        return $result;
    }
}
