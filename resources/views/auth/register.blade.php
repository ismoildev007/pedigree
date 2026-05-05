@extends('layouts.app')

@section('title', __('Register') . ' - ' . __('Shajara'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white text-center py-3">
                <h4 class="mb-0">{{ __('Create Account') }}</h4>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('Full Name') }}</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Phone Number') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">+998</span>
                            <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number') }}" required placeholder="901234567" maxlength="9" pattern="[0-9]{9}">
                        </div>
                        @error('phone_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Password') }}</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Confirm Password') }}</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2">{{ __('Register') }}</button>
                </form>
                <div class="text-center mt-3">
                    {{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Login here') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
