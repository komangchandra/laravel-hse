@extends('layouts.guest')
@section('title', 'Password Setup')

@section('content')
    <div class="mb-4">
        <h5 class="fw-bold text-dark mb-1">Set Account Password</h5>
        <p class="text-muted small">Secure your financial access credentials.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        
        <div class="mb-3">
            <label class="form-label fw-semibold small">Account Email</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person-fill text-primary"></i></span>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $email) }}" required autofocus autocomplete="username">
            </div>
            @error('email') <div class="text-danger x-small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold small">New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill text-primary"></i></span>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter new password" required autocomplete="new-password">
            </div>
            <div class="form-text">Minimal 8 karakter dengan huruf besar, huruf kecil, angka, dan simbol.</div>
            @error('password') <div class="text-danger x-small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold small">Confirm New Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-shield-check text-primary"></i></span>
                <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm password" required autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 shadow-sm">
            <i class="bi bi-check2-circle me-2"></i> Update & Secure Account
        </button>
    </form>
@endsection
