<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::with('manager')->withCount(['shops', 'stocks'])
            ->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%"))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->orderByRaw("FIELD(type, 'main', 'regional')")->orderBy('name')
            ->paginate(20)->withQueryString();

        foreach ($warehouses as $w) {
            $w->stock_units = $w->stocks()->sum('quantity');
        }

        return view('master.warehouses.index', compact('warehouses'));
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load('manager', 'parent');
        $shops = $warehouse->shops()->withSum('stocks as stock_units', 'quantity')->get();
        $topStock = $warehouse->stocks()->with('productVariant.product.brand')
            ->orderByDesc('quantity')->take(10)->get();
        $managers = User::where('role', User::ROLE_WAREHOUSE_MANAGER)->orderBy('name')->get();

        return view('master.warehouses.show', compact('warehouse', 'shops', 'topStock', 'managers'));
    }

    public function create()
    {
        return view('master.warehouses.form', [
            'warehouse' => new Warehouse(),
            'parents' => Warehouse::orderBy('name')->get(),
            'managers' => User::where('role', User::ROLE_WAREHOUSE_MANAGER)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Warehouse::create($this->validated($request));

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('master.warehouses.form', [
            'warehouse' => $warehouse,
            'parents' => Warehouse::where('id', '!=', $warehouse->id)->orderBy('name')->get(),
            'managers' => User::where('role', User::ROLE_WAREHOUSE_MANAGER)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $warehouse->update($this->validated($request, $warehouse));

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->shops()->exists() || $warehouse->outgoingTransfers()->exists()) {
            return back()->with('error', 'Cannot delete a warehouse that has shops or transfers.');
        }

        $warehouse->delete();

        return back()->with('success', 'Warehouse deleted.');
    }

    protected function validated(Request $request, ?Warehouse $warehouse = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code,' . ($warehouse?->id ?? 'NULL')],
            'type' => ['required', 'in:main,regional'],
            'parent_id' => ['nullable', 'exists:warehouses,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'owner_name' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
