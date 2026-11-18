@props(['dark' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('images/logo.png?v=20261118') }}" alt="" class="w-8 h-8 rounded-lg object-cover shadow-sm shadow-slate-900/20">
    <span class="font-bold tracking-tight text-[17px] {{ $dark ? 'text-white' : 'text-slate-900' }}">{{ config('app.name') }}</span>
</span>
