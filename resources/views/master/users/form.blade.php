@extends('layouts.app')
@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
<div class="row justify-content-center"><div class="col-lg-8">
    <div class="card">
        <div class="card-header"><i class="bi bi-person-gear me-2 text-primary"></i>{{ $user->exists ? 'Edit' : 'New' }} User</div>
        <div class="card-body">
            <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
                @csrf @if ($user->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full name *</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                        <x-field-error field="name" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                        <x-field-error field="email" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="role" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                        <x-field-error field="phone" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" value="{{ old('designation', $user->designation) }}" class="form-control">
                        <x-field-error field="designation" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                            <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspended</option>
                        </select>
                        <x-field-error field="status" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Warehouse assignment</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(old('warehouse_id', $user->warehouse_id) == $w->id)>{{ $w->name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="warehouse_id" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Shop assignment</label>
                        <select name="shop_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($shops as $shop)
                                <option value="{{ $shop->id }}" @selected(old('shop_id', $user->shop_id) == $shop->id)>{{ $shop->display_name }}</option>
                            @endforeach
                        </select>
                        <x-field-error field="shop_id" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password {{ $user->exists ? '(leave blank to keep)' : '*' }}</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control" @if (! $user->exists) required @endif>
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button>
                        </div>
                        <x-field-error field="password" />
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection
