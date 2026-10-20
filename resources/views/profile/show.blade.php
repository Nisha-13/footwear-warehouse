@extends('layouts.app')
@section('title', 'My Profile')
@section('subtitle', 'Account details and security')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="wms-avatar-lg mx-auto mb-3">{{ $user->initials }}</div>
                <h5 class="mb-1">{{ $user->name }}</h5>
                <div class="text-muted small mb-2">{{ $user->role_label }}</div>
                <span class="badge badge-soft-primary">{{ $user->email }}</span>
                <hr>
                <div class="text-start small">
                    <div class="mb-2"><span class="text-muted">Phone</span><div class="fw-semibold">{{ $user->phone ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Designation</span><div class="fw-semibold">{{ $user->designation ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Warehouse</span><div class="fw-semibold">{{ $user->warehouse?->name ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Shop</span><div class="fw-semibold">{{ $user->shop?->display_name ?? '—' }}</div></div>
                    <div class="mb-2"><span class="text-muted">Last login</span><div class="fw-semibold">{{ $user->last_login_at?->format('d M Y H:i') ?? '—' }}</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-gear me-2 text-primary"></i>Update Profile</div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PUT')
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
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                            <x-field-error field="phone" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" value="{{ old('designation', $user->designation) }}" class="form-control">
                            <x-field-error field="designation" />
                        </div>
                    </div>
                    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Changes</button></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Current password *</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            <x-field-error field="current_password" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New password *</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                            <x-field-error field="password" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm new password *</label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3"><button class="btn btn-warning"><i class="bi bi-key me-1"></i> Update Password</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
