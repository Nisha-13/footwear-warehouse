@extends('layouts.app')
@section('title', 'Users & Roles')
@section('subtitle', 'Manage accounts, roles and access across the network')

@section('content')
<div class="row g-3 mb-3">
    @foreach ($counts as $role => $count)
        <div class="col-6 col-lg-3 col-xl-2">
            <div class="card text-center p-3">
                <div class="fw-bold fs-4">{{ $count }}</div>
                <div class="text-muted small">{{ ucwords(str_replace('_', ' ', $role)) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name / email...">
            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All roles</option>
                @foreach (\App\Models\User::ROLES as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Any status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> Add User</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>User</th><th>Role</th><th>Assignment</th><th>Phone</th><th>Last Login</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="wms-avatar" style="width:32px;height:32px;font-size:.7rem">{{ $user->initials }}</span>
                            <div>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <div class="small text-muted">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-soft-primary">{{ $user->role_label }}</span></td>
                    <td class="small">
                        @if ($user->warehouse) <i class="bi bi-buildings me-1"></i>{{ $user->warehouse->name }}<br>@endif
                        @if ($user->shop) <i class="bi bi-shop me-1"></i>{{ $user->shop->display_name }}@endif
                        @unless ($user->warehouse || $user->shop) — @endunless
                    </td>
                    <td class="small">{{ $user->phone ?? '—' }}</td>
                    <td class="small text-muted">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                    <td>{!! status_badge($user->status) !!}</td>
                    <td class="text-end">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif
</div>
@endsection
