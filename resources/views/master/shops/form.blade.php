@extends('layouts.app')
@section('title', $shop->exists ? 'Edit Shop' : 'Add Shop')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <div class="card">
        <div class="card-header"><i class="bi bi-shop me-2 text-primary"></i>{{ $shop->exists ? 'Edit' : 'New' }} Retail Shop</div>
        <div class="card-body">
            <form method="POST" action="{{ $shop->exists ? route('shops.update', $shop) : route('shops.store') }}">
                @csrf @if ($shop->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Shop name *</label>
                        <input type="text" name="name" value="{{ old('name', $shop->name) }}" class="form-control" required>
                        <x-field-error field="name" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business name</label>
                        <input type="text" name="business_name" value="{{ old('business_name', $shop->business_name) }}" class="form-control">
                        <x-field-error field="business_name" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $shop->code) }}" class="form-control" required>
                        <x-field-error field="code" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warehouse *</label>
                        <select name="warehouse_id" class="form-select" required>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(old('warehouse_id', $shop->warehouse_id) == $w->id)>{{ $w->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="warehouse_id" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Shopkeeper</label>
                        <select name="shopkeeper_id" class="form-select">
                            <option value="">— Unassigned —</option>
                            @foreach ($shopkeepers as $keeper)
                                <option value="{{ $keeper->id }}" @selected(old('shopkeeper_id', $shop->shopkeeper_id) == $keeper->id)>{{ $keeper->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="shopkeeper_id" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner name *</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name', $shop->owner_name) }}" class="form-control" required>
                        <x-field-error field="owner_name" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $shop->phone) }}" class="form-control">
                        <x-field-error field="phone" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" value="{{ old('city', $shop->city) }}" class="form-control">
                        <x-field-error field="city" />
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" value="{{ old('address', $shop->address) }}" class="form-control">
                        <x-field-error field="address" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="latitude" value="{{ old('latitude', $shop->latitude) }}" class="form-control">
                        <x-field-error field="latitude" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="longitude" value="{{ old('longitude', $shop->longitude) }}" class="form-control">
                        <x-field-error field="longitude" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Credit limit</label>
                        <input type="number" name="credit_limit" value="{{ old('credit_limit', $shop->credit_limit) }}" class="form-control" step="0.01" min="0">
                        <x-field-error field="credit_limit" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $shop->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $shop->status) === 'inactive')>Inactive</option>
                        </select>
                        <x-field-error field="status" />
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('shops.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection
