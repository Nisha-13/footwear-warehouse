<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::withCount('purchases')
            ->when($request->q, fn ($q, $v) => $q->where('company_name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%"))
            ->orderBy('company_name')->paginate(20)->withQueryString();

        foreach ($suppliers as $s) {
            $s->outstanding = $s->outstanding;
        }

        return view('master.suppliers.index', compact('suppliers'));
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases' => fn ($q) => $q->latest()->take(10)]);
        $entries = $supplier->ledgerEntries()->latest('entry_date')->paginate(20);
        $outstanding = $supplier->outstanding;

        return view('master.suppliers.show', compact('supplier', 'entries', 'outstanding'));
    }

    public function create()
    {
        return view('master.suppliers.form', ['supplier' => new Supplier()]);
    }

    public function store(Request $request)
    {
        Supplier::create($this->validated($request));

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier)
    {
        return view('master.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('success', 'Supplier deleted.');
    }

    protected function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:40', 'unique:suppliers,code,' . ($supplier?->id ?? 'NULL')],
            'contact_person' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:60'],
            'bank_account' => ['nullable', 'string', 'max:80'],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
