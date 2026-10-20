@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
<div class="auth-shell">
    <aside class="auth-brand">
        <div class="auth-logo"><i class="bi bi-box-seam-fill"></i></div>
        <div class="auth-kicker"><i class="bi bi-diagram-3-fill"></i> Malik Enterprises</div>
        <h1>Footwear Warehouse Management System</h1>
        <p class="lead">
            A unified supply-chain platform connecting the Faisalabad Main Branch to regional
            warehouses and retail shops — with live inventory, procurement, logistics and analytics.
        </p>
        <ul class="auth-features">
            <li><i class="bi bi-building-check"></i> Multi-Warehouse</li>
            <li><i class="bi bi-truck"></i> Rider Logistics</li>
            <li><i class="bi bi-graph-up"></i> Live Analytics</li>
            <li><i class="bi bi-shield-check"></i> Role Security</li>
        </ul>
        <div class="auth-caption">&copy; {{ date('Y') }} Malik Enterprises. All rights reserved.</div>
    </aside>

    <main class="auth-panel">
        <div class="auth-card">
            <a href="{{ route('login') }}" class="auth-back"><i class="bi bi-arrow-left"></i> Back to sign in</a>

            <div class="auth-icon"><i class="bi bi-shield-check"></i></div>
            <h2>Set a new password</h2>
            <p class="auth-sub">Choose a strong password for <strong>{{ $email }}</strong>. You'll be signed in automatically after resetting.</p>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="At least 8 characters" minlength="8" required autofocus>
                        <button class="btn btn-pass-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    <x-field-error field="password" />
                </div>

                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Re-enter your new password" minlength="8" required>
                        <button class="btn btn-pass-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    <x-field-error field="password_confirmation" />
                </div>

                <button class="btn btn-auth w-100" type="submit">
                    <i class="bi bi-check2-circle me-1"></i> Reset Password
                </button>
            </form>

            <div class="auth-hint">
                <i class="bi bi-info-circle"></i>
                <span>For your security, all existing sessions stay signed out once the password changes.</span>
            </div>
        </div>
    </main>
</div>
@endsection
