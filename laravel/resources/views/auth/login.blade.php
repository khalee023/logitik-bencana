@extends('layouts.app')

@section('title', 'Login | DLCC ERP')

@section('content')
<div class="auth-container" style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="glass-panel" style="width: 100%; max-width: 400px; padding: 2rem;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">🛡️</div>
            <h2 style="color: var(--color-primary-light); margin: 0;">DLCC Access</h2>
            <p style="color: var(--color-text-muted); font-size: 0.9rem;">Disaster Logistics Command Center</p>
        </div>

        @if ($errors->any())
            <div class="alert" style="background: rgba(220, 53, 69, 0.2); border-left: 4px solid var(--color-danger); padding: 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm);">
                <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-text);">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="email" class="form-label" style="display: block; margin-bottom: 0.5rem;">Email Address</label>
                <div class="input-icon-wrapper">
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus placeholder="name@domain.go.id" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-glass-border); background: var(--color-bg); color: var(--color-text);">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="password" class="form-label" style="display: block; margin-bottom: 0.5rem;">Password</label>
                <div class="input-icon-wrapper">
                    <input id="password" type="password" class="form-control" name="password" required placeholder="••••••••" style="width: 100%; padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--color-glass-border); background: var(--color-bg); color: var(--color-text);">
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; justify-content: center;">
                    Authenticate Identity <i class="bi bi-arrow-right-short" style="vertical-align: middle;"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
