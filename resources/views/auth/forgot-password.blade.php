@extends('layouts.guest')

@section('title', 'Forgot Password')

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

            <div class="auth-icon"><i class="bi bi-keyhole"></i></div>
            <h2>Forgot your password?</h2>
            <p class="auth-sub">Enter the email address linked to your account and we'll send you a link to set a new password.</p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label" for="email">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
                    </div>
                    <x-field-error field="email" />
                </div>

                <button class="btn btn-auth w-100" type="submit">
                    <i class="bi bi-send me-1"></i> Send Reset Link
                </button>
            </form>

            <div class="auth-hint">
                <i class="bi bi-clock-history"></i>
                <span>Reset links expire after 60 minutes for your security. Don't forget to check your spam folder.</span>
            </div>
        </div>
    </main>
</div>
@endsection
