<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')->withCount(['products', 'ageGroups'])->orderBy('name')->paginate(20);

        return view('master.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('master.categories.form', [
            'category' => new Category(),
            'parents' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function show(Category $category)
    {
        $category->load('ageGroups', 'children');
        $products = Product::with(['brand', 'style'])
            ->where('category_id', $category->id)
            ->latest()->paginate(20);

        return view('master.categories.show', compact('category', 'products'));
    }

    public function edit(Category $category)
    {
        return view('master.categories.form', [
            'category' => $category,
            'parents' => Category::where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'gender' => ['required', 'in:men,women,kids,babies,unisex'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
