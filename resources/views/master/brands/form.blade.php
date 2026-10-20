@extends('layouts.app')
@section('title', $brand->exists ? 'Edit Brand' : 'Add Brand')

@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
    <div class="card">
        <div class="card-header"><i class="bi bi-award me-2 text-primary"></i>{{ $brand->exists ? 'Edit' : 'New' }} Brand</div>
        <div class="card-body">
            <form method="POST" action="{{ $brand->exists ? route('brands.update', $brand) : route('brands.store') }}">
                @csrf
                @if ($brand->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Brand name *</label>
                        <input type="text" name="name" value="{{ old('name', $brand->name) }}" class="form-control" required>
                        <x-field-error field="name" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sub-brand</label>
                        <input type="text" name="sub_brand" value="{{ old('sub_brand', $brand->sub_brand) }}" class="form-control">
                        <x-field-error field="sub_brand" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" value="{{ old('country', $brand->country) }}" class="form-control">
                        <x-field-error field="country" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" value="{{ old('website', $brand->website) }}" class="form-control">
                        <x-field-error field="website" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $brand->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $brand->status) === 'inactive')>Inactive</option>
                        </select>
                        <x-field-error field="status" />
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('brands.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection
