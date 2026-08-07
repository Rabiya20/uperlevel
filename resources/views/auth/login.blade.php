@extends('layouts.base')

@section('title', 'Sign in — UperLevel')
@section('body-class', 'theme-login')

@section('content')
<div class="login-card">
    <div class="login-brand">
        <div class="login-mark">UL</div>
        <h1>UperLevel</h1>
        <p>Simplifying Operations for Modern Tech Company</p>
    </div>

    @if ($errors->any())
        <div class="login-error">{{ $errors->first() }}</div>
    @endif

    @if (session('status'))
        <div class="login-error" style="background:#E7F8EF;color:#0F7C50;">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.attempt') }}">
        @csrf
        <div class="field">
            <label for="email">Work email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="login-row">
            <label style="display:flex;align-items:center;gap:6px;">
                <input type="checkbox" name="remember" style="width:auto;"> Remember me
            </label>
            <span>Forgot password?</span>
        </div>
        <button type="submit" class="btn-login">Sign in</button>
    </form>

    <!-- <div class="login-hint">
        Demo accounts (password: <b>password</b>)<br>
        Super Admin — superadmin@techflow.app<br>
        Owner — owner@pixelworks.co <br>
        Admin — admin@pixelworks.co<br>
        Manager — manager@pixelworks.co <br>
        Employee — employee@pixelworks.co
    </div> -->
</div>
@endsection
