@extends('layouts.app')
@section('title', $category->exists ? 'Edit Category' : 'Add Category')

@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
    <div class="card">
        <div class="card-header"><i class="bi bi-diagram-3 me-2 text-primary"></i>{{ $category->exists ? 'Edit' : 'New' }} Category</div>
        <div class="card-body">
            <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
                @csrf @if ($category->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control" required>
                        <x-field-error field="name" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent category</label>
                        <select name="parent_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="parent_id" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gender / Segment *</label>
                        <select name="gender" class="form-select" required>
                            @foreach (['men', 'women', 'kids', 'babies', 'unisex'] as $g)
                                <option value="{{ $g }}" @selected(old('gender', $category->gender) === $g)>{{ ucfirst($g) }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="gender" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" value="{{ old('description', $category->description) }}" class="form-control">
                        <x-field-error field="description" />
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection
