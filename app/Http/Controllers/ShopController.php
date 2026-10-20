<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $shops = Shop::with(['warehouse', 'shopkeeper'])
            ->when(! $user->isSuperAdmin() && $user->warehouse_id, fn ($q) => $q->where('warehouse_id', $user->warehouse_id))
            ->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")->orWhere('business_name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%"))
            ->when($request->warehouse_id, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->orderBy('name')->paginate(20)->withQueryString();

        foreach ($shops as $s) {
            $s->stock_units = $s->stocks()->sum('quantity');
        }

        $warehouses = Warehouse::orderBy('name')->get();

        return view('master.shops.index', compact('shops', 'warehouses'));
    }

    public function show(Shop $shop)
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin() && (int) $user->warehouse_id !== (int) $shop->warehouse_id) {
            abort(403, 'You are not authorized to view this shop.');
        }

        $shop->load('warehouse', 'shopkeeper');
        $topStock = $shop->stocks()->with('productVariant.product.brand')->orderByDesc('quantity')->take(10)->get();
        $sales = $shop->sales()->latest()->take(10)->get();
        $shopkeepers = User::where('role', User::ROLE_SHOPKEEPER)->orderBy('name')->get();

        return view('master.shops.show', compact('shop', 'topStock', 'sales', 'shopkeepers'));
    }

    public function create()
    {
        return view('master.shops.form', [
            'shop' => new Shop(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'shopkeepers' => User::where('role', User::ROLE_SHOPKEEPER)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Shop::create($this->validated($request));

        return redirect()->route('shops.index')->with('success', 'Shop created successfully.');
    }

    public function edit(Shop $shop)
    {
        return view('master.shops.form', [
            'shop' => $shop,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'shopkeepers' => User::where('role', User::ROLE_SHOPKEEPER)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Shop $shop)
    {
        $shop->update($this->validated($request, $shop));

        return redirect()->route('shops.index')->with('success', 'Shop updated successfully.');
    }

    public function destroy(Shop $shop)
    {
        if ($shop->sales()->exists()) {
            return back()->with('error', 'Cannot delete a shop that has recorded sales.');
        }

        $shop->delete();

        return back()->with('success', 'Shop deleted.');
    }

    protected function validated(Request $request, ?Shop $shop = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'business_name' => ['nullable', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:40', 'unique:shops,code,' . ($shop?->id ?? 'NULL')],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'shopkeeper_id' => ['nullable', 'exists:users,id'],
            'owner_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
