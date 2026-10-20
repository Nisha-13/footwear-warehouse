<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Supplier;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $entries = LedgerEntry::with(['party', 'user'])
            ->when($request->q, fn ($q, $v) => $q->where('entry_no', 'like', "%{$v}%")->orWhere('description', 'like', "%{$v}%"))
            ->when($request->supplier_id, fn ($q, $v) => $q->where('party_type', Supplier::class)->where('party_id', $v))
            ->when($request->payment_status, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('entry_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('entry_date', '<=', $v))
            ->latest('entry_date')->latest('id')->paginate(25)->withQueryString();

        $suppliers = Supplier::orderBy('company_name')->get();

        $summary = [
            'total_debit' => (float) LedgerEntry::sum('debit'),
            'total_credit' => (float) LedgerEntry::sum('credit'),
            'outstanding' => (float) (LedgerEntry::sum('debit') - LedgerEntry::sum('credit')),
        ];

        return view('procurement.ledger.index', compact('entries', 'suppliers', 'summary'));
    }

    public function supplier(Supplier $supplier)
    {
        $entries = $supplier->ledgerEntries()->with('user')->latest('entry_date')->paginate(30);
        $outstanding = $supplier->outstanding;

        return view('procurement.ledger.supplier', compact('supplier', 'entries', 'outstanding'));
    }
}
