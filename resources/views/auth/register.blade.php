@extends('layouts.app')

@section('title', 'Register | AINCHORS')

@section('content')
<section class="account-page">
    <div class="account-card">
        <span class="eyebrow">AINCHORS Learning</span>
        <h1>Create your account</h1>
        <p>Register to purchase and access protected self-learning courses.</p>

        <form method="POST" action="{{ route('register.store') }}" class="stacked-form">
            @csrf
            <label>Name<input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus></label>
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
            <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
            <label>Password<input type="password" name="password" autocomplete="new-password" required></label>
            @error('password')<p class="form-error">{{ $message }}</p>@enderror
            <label>Confirm Password<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
            <button type="submit" class="primary-button form-button">Register</button>
        </form>

        <p class="account-switch">Already registered? <a href="{{ route('login') }}">Login</a></p>
    </div>
</section>
@endsection
