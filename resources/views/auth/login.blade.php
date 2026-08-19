@extends('layouts.app')

@section('title', 'Login | AINCHORS')

@section('content')
<section class="account-page">
    <div class="account-card">
        <span class="eyebrow">AINCHORS Learning</span>
        <h1>Welcome back</h1>
        <p>Log in to continue to checkout or access your courses.</p>

        <form method="POST" action="{{ route('login.store') }}" class="stacked-form">
            @csrf
            <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label>
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
            <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
            @error('password')<p class="form-error">{{ $message }}</p>@enderror
            <label class="checkbox-row"><input type="checkbox" name="remember" value="1"> Remember me</label>
            <button type="submit" class="primary-button form-button">Login</button>
        </form>

        <p class="account-switch">New to AINCHORS? <a href="{{ route('register') }}">Create an account</a></p>
    </div>
</section>
@endsection
