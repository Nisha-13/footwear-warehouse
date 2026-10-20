<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\User;
use App\Support\NumberGenerator;
use App\Support\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopSalesController extends Controller
{
    public function __construct(protected StockService $stockService) {}

    public function index()
    {
        $user = auth()->user();
        $shop = $user->shop;
        $sales = Sale::with('items.productVariant.product')
            ->where('shop_id', $shop?->id)
            ->latest()->paginate(20);

        return view('shop.sales.index', compact('sales', 'shop'));
    }

    public function create()
    {
        $user = auth()->user();
        $shop = $user->shop;

        $stocks = Stock::with('productVariant.product.brand')
            ->where('stockable_type', Shop::class)
            ->where('stockable_id', $shop?->id)
            ->where('quantity', '>', 0)
            ->get();

        return view('shop.sales.create', compact('shop', 'stocks'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'payment_mode' => ['required', 'in:full_cash,credit,card,online'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $sale = DB::transaction(function () use ($data, $user, $shop) {
            $subtotal = 0;
            $itemCount = 0;

            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
                $itemCount += $item['quantity'];
            }

            $total = $subtotal;
            $paid = $data['payment_mode'] === 'full_cash' ? $total : 0;
            $balance = $total - $paid;

            $sale = Sale::create([
                'invoice_no' => NumberGenerator::saleInvoice(),
                'shop_id' => $shop->id,
                'user_id' => $user->id,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'sale_date' => today(),
                'item_count' => $itemCount,
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => 0,
                'total' => $total,
                'paid_amount' => $paid,
                'balance' => $balance,
                'payment_mode' => $data['payment_mode'],
                'payment_status' => $balance <= 0 ? 'paid' : 'unpaid',
            ]);

            foreach ($data['items'] as $item) {
                $variant = \App\Models\ProductVariant::find($item['product_variant_id']);
                \App\Models\SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['unit_price'] * $item['quantity'],
                ]);

                $this->stockService->remove($variant, $shop, (int) $item['quantity'], 'sale', [
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                ]);
            }

            return $sale;
        });

        return redirect()->route('shop.sales.index')->with('success', 'Sale recorded. Invoice ' . $sale->invoice_no);
    }

    public function show(Sale $sale)
    {
        $sale->load('items.productVariant.product', 'shop', 'user');

        return view('shop.sales.show', compact('sale'));
    }

    public function inventory()
    {
        $user = auth()->user();
        $shop = $user->shop;
        $stocks = Stock::with('productVariant.product.brand')
            ->where('stockable_type', Shop::class)
            ->where('stockable_id', $shop?->id)
            ->orderByDesc('quantity')->paginate(25);

        return view('shop.inventory', compact('stocks', 'shop'));
    }
}
