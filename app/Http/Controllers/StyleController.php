<?php

namespace App\Http\Controllers;

use App\Models\Style;
use Illuminate\Http\Request;

class StyleController extends Controller
{
    public function index(Request $request)
    {
        $styles = Style::withCount('products')
            ->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('master.styles.index', compact('styles'));
    }

    public function create()
    {
        return view('master.styles.form', ['style' => new Style()]);
    }

    public function store(Request $request)
    {
        Style::create($this->validated($request));

        return redirect()->route('styles.index')->with('success', 'Style created successfully.');
    }

    public function edit(Style $style)
    {
        return view('master.styles.form', compact('style'));
    }

    public function update(Request $request, Style $style)
    {
        $style->update($this->validated($request, $style));

        return redirect()->route('styles.index')->with('success', 'Style updated successfully.');
    }

    public function destroy(Style $style)
    {
        $style->delete();

        return back()->with('success', 'Style deleted.');
    }

    protected function validated(Request $request, ?Style $style = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:styles,name,' . ($style?->id ?? 'NULL')],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
