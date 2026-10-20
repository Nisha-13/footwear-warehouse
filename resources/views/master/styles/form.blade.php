@extends('layouts.app')
@section('title', $style->exists ? 'Edit Style' : 'Add Style')

@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
    <div class="card">
        <div class="card-header"><i class="bi bi-palette me-2 text-primary"></i>{{ $style->exists ? 'Edit' : 'New' }} Style</div>
        <div class="card-body">
            <form method="POST" action="{{ $style->exists ? route('styles.update', $style) : route('styles.store') }}">
                @csrf @if ($style->exists) @method('PUT') @endif
                <div class="mb-3">
                    <label class="form-label">Style name *</label>
                    <input type="text" name="name" value="{{ old('name', $style->name) }}" class="form-control" required>
                    <x-field-error field="name" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" value="{{ old('description', $style->description) }}" class="form-control">
                    <x-field-error field="description" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select">
                        <option value="active" @selected(old('status', $style->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $style->status) === 'inactive')>Inactive</option>
                    </select>
                    <x-field-error field="status" />
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('styles.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection
