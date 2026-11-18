@props(['dark' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('images/logo.png?v=20261118d') }}" alt="" class="brand-logo-mark w-14 h-14 rounded-xl bg-slate-100 object-cover p-1 ring-1 ring-slate-200/80 shadow-sm shadow-slate-900/10">
    <span class="brand-logo-name font-bold tracking-tight text-[17px] {{ $dark ? 'text-white' : 'text-slate-900' }}">{{ config('app.name') }}</span>
</span>
