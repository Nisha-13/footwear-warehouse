@extends('layouts.app')
@section('title', $ageGroup->exists ? 'Edit Age Group' : 'Add Age Group')

@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
    <div class="card">
        <div class="card-header"><i class="bi bi-rulers me-2 text-primary"></i>{{ $ageGroup->exists ? 'Edit' : 'New' }} Age Group</div>
        <div class="card-body">
            <form method="POST" action="{{ $ageGroup->exists ? route('age-groups.update', $ageGroup) : route('age-groups.store') }}">
                @csrf @if ($ageGroup->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $ageGroup->name) }}" class="form-control" placeholder="e.g. Toddler" required>
                        <x-field-error field="name" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $ageGroup->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="category_id" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Min age (months) *</label>
                        <input type="number" name="min_age_months" value="{{ old('min_age_months', $ageGroup->min_age_months ?? 0) }}" class="form-control" min="0" required>
                        <x-field-error field="min_age_months" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Max age (months)</label>
                        <input type="number" name="max_age_months" value="{{ old('max_age_months', $ageGroup->max_age_months) }}" class="form-control" min="0">
                        <x-field-error field="max_age_months" />
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('age-groups.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection
