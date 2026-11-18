@extends('layouts.guest')
@section('title', 'Create your account')
@section('subtitle', 'Start accepting UPI payments in minutes. Free, no setup fee.')

@section('content')
@if ($registrationEnabled)
    <form method="POST" action="{{ route('register') }}" data-loading-form>
        @csrf
        <div class="grid sm:grid-cols-2 gap-x-3">
            <x-input name="name" label="Full name" required autofocus autocomplete="name" />
            <x-input name="business_name" label="Business / merchant name" required autocomplete="organization" />
        </div>
        <x-input name="email" label="Email" type="email" required autocomplete="email" placeholder="you@business.com" />
        <x-input name="phone" label="Phone number (optional)" placeholder="Optional" autocomplete="tel" />
        <div class="grid sm:grid-cols-2 gap-x-3">
            <x-input name="password" label="Password" type="password" required autocomplete="new-password" placeholder="At least 10 characters" />
            <x-input name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />
        </div>
        <button type="submit" class="btn btn-primary w-full py-2.5 mt-1" data-loading-label="Creating account...">
            <span data-submit-label>Create account</span> <x-icon name="arrow-right" class="w-4 h-4" />
        </button>
        <p class="text-xs text-center text-slate-400 mt-4">Payments go directly to your UPI account. We never hold your funds.</p>
    </form>
@else
    <div role="status" class="rounded-lg bg-amber-50 ring-1 ring-amber-200 px-4 py-3 text-sm text-amber-800">
        Merchant registration is temporarily unavailable. Please try again later.
    </div>
@endif
<div class="relative my-6">
    <div class="absolute inset-0 flex items-center"><span class="w-full border-t border-slate-200"></span></div>
    <div class="relative flex justify-center"><span class="bg-white px-3 text-xs text-slate-400">OR</span></div>
</div>
<a href="{{ route('google.redirect') }}" class="btn w-full justify-center gap-2 border border-slate-200 bg-white py-2.5 text-slate-700 hover:bg-slate-50">
    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94a11.05 11.05 0 0 1-4.8 7.24l7.73 6C44.38 38.05 47 31.95 47 24.55Z"/><path fill="#FBBC05" d="M10.53 28.59a14.4 14.4 0 0 1 0-9.18l-7.98-6.2a23.9 23.9 0 0 0 0 21.58l7.98-6.2Z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.9-5.8l-7.74-6c-2.15 1.45-4.9 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.2C6.51 42.62 14.62 48 24 48Z"/></svg>
    Continue with Google
</a>
<p class="text-sm text-center text-slate-500 mt-8">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Log in</a></p>
@endsection
