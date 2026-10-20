<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Style;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['brand', 'style', 'category'])
            ->withCount('variants')
            ->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%{$v}%")->orWhere('sku', 'like', "%{$v}%"))
            ->when($request->brand_id, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($request->category_id, fn ($q, $v) => $q->where('category_id', $v))
            ->when($request->style_id, fn ($q, $v) => $q->where('style_id', $v))
            ->latest()->paginate(15)->withQueryString();

        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $styles = Style::orderBy('name')->get();

        return view('master.products.index', compact('products', 'brands', 'categories', 'styles'));
    }

    public function show(Product $product)
    {
        $product->load(['brand', 'style', 'category', 'ageGroup', 'variants' => fn ($q) => $q->withSum('stocks as total_stock', 'quantity')]);

        return view('master.products.show', compact('product'));
    }

    public function create()
    {
        return view('master.products.form', [
            'product' => new Product(),
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'ageGroups' => \App\Models\AgeGroup::orderBy('name')->get(),
            'styles' => Style::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sku'] = $data['sku'] ?: 'PRD-' . strtoupper(Str::random(6));

        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);
            $this->syncVariants($product, $request);

            return $product;
        });

        return redirect()->route('products.show', $product)->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');

        return view('master.products.form', [
            'product' => $product,
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'ageGroups' => \App\Models\AgeGroup::orderBy('name')->get(),
            'styles' => Style::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($product, $data, $request) {
            $product->update($data);
            $this->syncVariants($product, $request);
        });

        return redirect()->route('products.show', $product)->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function addVariant(Request $request, Product $product)
    {
        $data = $request->validate([
            'size' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'max:40'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['product_id'] = $product->id;
        $data['sku'] = $product->sku . '-' . strtoupper(Str::slug($data['color'], '')) . '-' . Str::slug($data['size'], '');
        $data['status'] = 'active';

        ProductVariant::updateOrCreate(
            ['product_id' => $product->id, 'size' => $data['size'], 'color' => $data['color']],
            $data
        );

        return back()->with('success', 'Variant saved.');
    }

    public function destroyVariant(ProductVariant $variant)
    {
        $variant->delete();

        return back()->with('success', 'Variant removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:60', 'unique:products,sku,' . ($request->route('product')?->id ?? 'NULL')],
            'barcode' => ['nullable', 'string', 'max:60'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'age_group_id' => ['nullable', 'exists:age_groups,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'style_id' => ['nullable', 'exists:styles,id'],
            'gender' => ['required', 'in:men,women,kids,babies,unisex'],
            'description' => ['nullable', 'string'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'retail_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    protected function syncVariants(Product $product, Request $request): void
    {
        $sizes = $request->input('variant_size', []);
        $colors = $request->input('variant_color', []);
        $prices = $request->input('variant_price', []);
        $costs = $request->input('variant_cost', []);

        foreach ($sizes as $i => $size) {
            if (empty($size) || empty($colors[$i] ?? null)) {
                continue;
            }

            ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'size' => $size, 'color' => $colors[$i]],
                [
                    'cost_price' => $costs[$i] ?? $product->cost_price,
                    'price' => $prices[$i] ?? $product->retail_price,
                    'sku' => $product->sku . '-' . strtoupper(Str::slug($colors[$i], '')) . '-' . Str::slug($size, ''),
                    'status' => 'active',
                ]
            );
        }
    }
}
