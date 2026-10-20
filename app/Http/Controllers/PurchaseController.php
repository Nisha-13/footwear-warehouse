<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Support\NumberGenerator;
use App\Support\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $purchases = Purchase::with(['supplier', 'warehouse'])
            ->when($request->q, fn ($q, $v) => $q->where('invoice_no', 'like', "%{$v}%"))
            ->when($request->supplier_id, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($request->warehouse_id, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->payment_status, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('purchase_date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('purchase_date', '<=', $v))
            ->latest('purchase_date')->latest('id')->paginate(20)->withQueryString();

        $suppliers = Supplier::orderBy('company_name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('procurement.purchases.index', compact('purchases', 'suppliers', 'warehouses'));
    }

    public function create()
    {
        return view('procurement.purchases.form', [
            'suppliers' => Supplier::where('status', 'active')->orderBy('company_name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'variants' => $this->variantOptions(),
        ]);
    }

    public function store(Request $request, StockService $stockService)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'purchase_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $purchase = DB::transaction(function () use ($data, $stockService) {
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $subtotal = 0;
            $itemCount = 0;

            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_cost'];
                $itemCount += $item['quantity'];
            }

            $total = $subtotal + ($data['tax'] ?? 0) + ($data['shipping'] ?? 0) - ($data['discount'] ?? 0);
            $paid = $data['paid_amount'] ?? 0;
            $balance = max(0, $total - $paid);

            $purchase = Purchase::create([
                'invoice_no' => NumberGenerator::invoice(),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => auth()->id(),
                'purchase_date' => $data['purchase_date'],
                'item_count' => $itemCount,
                'subtotal' => $subtotal,
                'tax' => $data['tax'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'shipping' => $data['shipping'] ?? 0,
                'total' => $total,
                'paid_amount' => $paid,
                'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                'payment_method' => $data['payment_method'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'status' => 'received',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total' => $item['quantity'] * $item['unit_cost'],
                ]);

                $variant = ProductVariant::with('product')->find($item['product_variant_id']);
                $stockService->add($variant, $warehouse, (int) $item['quantity'], 'purchase', [
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'Received via purchase ' . $purchase->invoice_no,
                ]);

                if ($variant->product) {
                    $variant->product->update(['cost_price' => $item['unit_cost']]);
                }
            }

            LedgerEntry::create([
                'entry_no' => NumberGenerator::ledger(),
                'party_type' => Supplier::class,
                'party_id' => $purchase->supplier_id,
                'entry_type' => 'payable',
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'item_count' => $itemCount,
                'debit' => $total,
                'credit' => $paid,
                'balance' => $balance,
                'payment_status' => $purchase->payment_status,
                'payment_method' => $data['payment_method'] ?? null,
                'entry_date' => $data['purchase_date'],
                'due_date' => $data['due_date'] ?? null,
                'description' => 'Purchase ' . $purchase->invoice_no . ' from supplier',
                'user_id' => auth()->id(),
            ]);

            return $purchase;
        });

        return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase recorded and stock updated.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'warehouse', 'items.productVariant.product', 'ledgerEntries', 'user']);

        return view('procurement.purchases.show', compact('purchase'));
    }

    public function destroy(Purchase $purchase, StockService $stockService)
    {
        DB::transaction(function () use ($purchase, $stockService) {
            foreach ($purchase->items as $item) {
                $stockService->remove($item->productVariant, $purchase->warehouse, $item->quantity, 'purchase_reversal', [
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'note' => 'Purchase ' . $purchase->invoice_no . ' reversed',
                ], allowNegative: true);
            }

            LedgerEntry::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->delete();
            $purchase->delete();
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase reversed and stock restored.');
    }

    public function addPayment(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . max(0.01, $purchase->balance)],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'entry_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($purchase, $data) {
            $purchase->paid_amount += $data['amount'];
            $purchase->balance = max(0, $purchase->total - $purchase->paid_amount);
            $purchase->payment_status = $purchase->balance <= 0 ? 'paid' : 'partial';
            $purchase->save();

            LedgerEntry::create([
                'entry_no' => NumberGenerator::ledger(),
                'party_type' => Supplier::class,
                'party_id' => $purchase->supplier_id,
                'entry_type' => 'payable',
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'item_count' => 0,
                'debit' => 0,
                'credit' => $data['amount'],
                'balance' => $purchase->balance,
                'payment_status' => $purchase->payment_status,
                'payment_method' => $data['payment_method'] ?? $purchase->payment_method,
                'entry_date' => $data['entry_date'],
                'description' => 'Payment against ' . $purchase->invoice_no,
                'user_id' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Payment recorded successfully.');
    }

    protected function variantOptions()
    {
        return ProductVariant::with('product.brand')
            ->where('status', 'active')
            ->get()
            ->sortBy(fn ($v) => $v->product?->name)
            ->values();
    }
}
