@extends('layouts.guest')

@section('title', 'Sign In')

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
            <div class="auth-icon"><i class="bi bi-person-badge-fill"></i></div>
            <h2>Welcome back</h2>
            <p class="auth-sub">Sign in to your workspace to continue.</p>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                    </div>
                    <x-field-error field="email" />
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0" for="password">Password</label>
                        <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        <button class="btn btn-pass-toggle" type="button" data-password-toggle aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                    <x-field-error field="password" />
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label auth-remember" for="remember">Keep me signed in on this device</label>
                </div>

                <button class="btn btn-auth w-100" type="submit">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>

            <div class="auth-hint">
                <i class="bi bi-shield-lock"></i>
                <span>Access is restricted by role. Contact your administrator if you need an account or your access has changed.</span>
            </div>
        </div>
    </main>
</div>
@endsection
