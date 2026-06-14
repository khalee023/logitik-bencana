@extends('layouts.app')

@section('title', 'Login | DLCC ERP')

@section('content')
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">🛡️</div>
            <h1>DLCC Access</h1>
            <p>Disaster Logistics Command Center</p>
        </div>

        @if ($errors->any())
            <div style="background: rgba(255, 51, 102, 0.1); border: 1px solid rgba(255, 51, 102, 0.25); padding: var(--space-md); margin-bottom: var(--space-lg); border-radius: var(--radius-md);">
                <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-text-primary); font-size: 0.85rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus placeholder="name@domain.go.id">
            </div>

            <div class="form-group" style="margin-bottom: var(--space-xl);">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" class="form-control" name="password" required placeholder="••••••••">
            </div>

            <div>
                <button type="submit" class="btn btn-primary btn-pulse w-full" style="padding: 0.8rem; justify-content: center;">
                    Authenticate Identity <i class="bi bi-arrow-right-short" style="vertical-align: middle;"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
