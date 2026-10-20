<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = Brand::withCount('products')
            ->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")->orWhere('sub_brand', 'like', "%{$v}%"))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('master.brands.index', compact('brands'));
    }

    public function create()
    {
        return view('master.brands.form', ['brand' => new Brand()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Brand::create($data);

        return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
    }

    public function edit(Brand $brand)
    {
        return view('master.brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $brand->update($this->validated($request, $brand));

        return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return back()->with('success', 'Brand deleted.');
    }

    protected function validated(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:brands,name,' . ($brand?->id ?? 'NULL')],
            'sub_brand' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'website' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
