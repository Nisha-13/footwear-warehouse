@extends('layouts.app')
@section('title', $warehouse->exists ? 'Edit Warehouse' : 'Add Warehouse')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <div class="card">
        <div class="card-header"><i class="bi bi-buildings me-2 text-primary"></i>{{ $warehouse->exists ? 'Edit' : 'New' }} Warehouse</div>
        <div class="card-body">
            <form method="POST" action="{{ $warehouse->exists ? route('warehouses.update', $warehouse) : route('warehouses.store') }}">
                @csrf @if ($warehouse->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Warehouse name *</label>
                        <input type="text" name="name" value="{{ old('name', $warehouse->name) }}" class="form-control" required>
                        <x-field-error field="name" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $warehouse->code) }}" class="form-control" placeholder="LHR-REG" required>
                        <x-field-error field="code" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-select" required>
                            <option value="main" @selected(old('type', $warehouse->type) === 'main')>Main Branch</option>
                            <option value="regional" @selected(old('type', $warehouse->type) === 'regional')>Regional</option>
                        </select>
                        <x-field-error field="type" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Parent warehouse</label>
                        <select name="parent_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id', $warehouse->parent_id) == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="parent_id" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Manager</label>
                        <select name="manager_id" class="form-select">
                            <option value="">— Unassigned —</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}" @selected(old('manager_id', $warehouse->manager_id) == $manager->id)>{{ $manager->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="manager_id" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner name</label>
                        <input type="text" name="owner_name" value="{{ old('owner_name', $warehouse->owner_name) }}" class="form-control">
                        <x-field-error field="owner_name" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $warehouse->phone) }}" class="form-control">
                        <x-field-error field="phone" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" value="{{ old('city', $warehouse->city) }}" class="form-control">
                        <x-field-error field="city" />
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" value="{{ old('address', $warehouse->address) }}" class="form-control">
                        <x-field-error field="address" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Latitude</label>
                        <input type="text" name="latitude" value="{{ old('latitude', $warehouse->latitude) }}" class="form-control" placeholder="31.418715">
                        <x-field-error field="latitude" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Longitude</label>
                        <input type="text" name="longitude" value="{{ old('longitude', $warehouse->longitude) }}" class="form-control" placeholder="73.079109">
                        <x-field-error field="longitude" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Capacity (units)</label>
                        <input type="number" name="capacity" value="{{ old('capacity', $warehouse->capacity) }}" class="form-control">
                        <x-field-error field="capacity" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $warehouse->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $warehouse->status) === 'inactive')>Inactive</option>
                        </select>
                        <x-field-error field="status" />
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('warehouses.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection
