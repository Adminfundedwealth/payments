@extends('layouts.guest')
@section('title', 'Welcome back')
@section('subtitle', 'Log in to your merchant dashboard.')

@section('content')
<form method="POST" action="{{ route('login') }}" data-loading-form>
    @csrf
    <div class="mb-5">
        <label for="f-email" class="label">Email</label>
        <input id="f-email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" @class(['input py-2.5', 'ring-red-400 focus:ring-red-500' => $errors->has('email')]) placeholder="you@business.com">
    </div>
    <div class="mb-5">
        <label for="f-password" class="label">Password</label>
        <input id="f-password" type="password" name="password" required autocomplete="current-password" @class(['input py-2.5', 'ring-red-400 focus:ring-red-500' => $errors->has('password')]) placeholder="••••••••">
    </div>
    <label class="flex items-center gap-2 text-sm text-slate-600 mb-6">
        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-600"> Keep me logged in
    </label>
    <button type="submit" class="btn btn-primary w-full py-2.5" data-loading-label="Signing in...">
        <span data-submit-label>Log in</span> <x-icon name="arrow-right" class="w-4 h-4" />
    </button>
</form>
<div class="relative my-6">
    <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-slate-200"></span></div>
    <div class="relative flex justify-center"><span class="bg-white px-3 text-xs text-slate-400">OR</span></div>
</div>
<a href="{{ route('google.redirect') }}" class="btn w-full justify-center gap-2 border border-slate-200 bg-white py-2.5 text-slate-700 hover:bg-slate-50">
    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94a11.05 11.05 0 0 1-4.8 7.24l7.73 6C44.38 38.05 47 31.95 47 24.55Z"/><path fill="#FBBC05" d="M10.53 28.59a14.4 14.4 0 0 1 0-9.18l-7.98-6.2a23.9 23.9 0 0 0 0 21.58l7.98-6.2Z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.9-5.8l-7.74-6c-2.15 1.45-4.9 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.2C6.51 42.62 14.62 48 24 48Z"/></svg>
    Continue with Google
</a>
<p class="text-sm text-center text-slate-500 mt-8">New to {{ config('app.name') }}? <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">Sign Up</a></p>
@endsection
