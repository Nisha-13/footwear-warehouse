@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit Supplier' : 'Add Supplier')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
    <div class="card">
        <div class="card-header"><i class="bi bi-truck me-2 text-primary"></i>{{ $supplier->exists ? 'Edit' : 'New' }} Supplier</div>
        <div class="card-body">
            <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
                @csrf @if ($supplier->exists) @method('PUT') @endif
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Company name *</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $supplier->company_name) }}" class="form-control" required>
                        <x-field-error field="company_name" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $supplier->code) }}" class="form-control" required>
                        <x-field-error field="code" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="form-control">
                        <x-field-error field="contact_person" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control">
                        <x-field-error field="email" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control">
                        <x-field-error field="phone" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" value="{{ old('city', $supplier->city) }}" class="form-control">
                        <x-field-error field="city" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" value="{{ old('country', $supplier->country ?? 'Pakistan') }}" class="form-control">
                        <x-field-error field="country" />
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" value="{{ old('address', $supplier->address) }}" class="form-control">
                        <x-field-error field="address" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tax / NTN</label>
                        <input type="text" name="tax_number" value="{{ old('tax_number', $supplier->tax_number) }}" class="form-control">
                        <x-field-error field="tax_number" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bank account</label>
                        <input type="text" name="bank_account" value="{{ old('bank_account', $supplier->bank_account) }}" class="form-control">
                        <x-field-error field="bank_account" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Opening balance</label>
                        <input type="number" name="opening_balance" value="{{ old('opening_balance', $supplier->opening_balance ?? 0) }}" class="form-control" step="0.01">
                        <x-field-error field="opening_balance" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $supplier->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $supplier->status) === 'inactive')>Inactive</option>
                        </select>
                        <x-field-error field="status" />
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection
