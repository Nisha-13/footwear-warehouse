<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\Category;
use Illuminate\Http\Request;

class AgeGroupController extends Controller
{
    public function index()
    {
        $ageGroups = AgeGroup::with('category')->withCount('products')->orderBy('min_age_months')->paginate(20);

        return view('master.age_groups.index', compact('ageGroups'));
    }

    public function create()
    {
        return view('master.age_groups.form', [
            'ageGroup' => new AgeGroup(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        AgeGroup::create($this->validated($request));

        return redirect()->route('age-groups.index')->with('success', 'Age group created.');
    }

    public function edit(AgeGroup $ageGroup)
    {
        return view('master.age_groups.form', [
            'ageGroup' => $ageGroup,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, AgeGroup $ageGroup)
    {
        $ageGroup->update($this->validated($request));

        return redirect()->route('age-groups.index')->with('success', 'Age group updated.');
    }

    public function destroy(AgeGroup $ageGroup)
    {
        $ageGroup->delete();

        return back()->with('success', 'Age group deleted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'min_age_months' => ['required', 'integer', 'min:0'],
            'max_age_months' => ['nullable', 'integer', 'gte:min_age_months'],
        ]);
    }
}
