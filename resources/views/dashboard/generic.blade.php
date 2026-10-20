@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-info-circle display-4 text-muted"></i>
        <h4 class="mt-3">Welcome, {{ auth()->user()->name }}</h4>
        <p class="text-muted mb-0">{{ $message ?? 'Your workspace is being set up. Please contact the administrator.' }}</p>
    </div>
</div>
@endsection
