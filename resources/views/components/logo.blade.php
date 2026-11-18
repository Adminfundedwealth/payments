@props(['dark' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <img src="{{ asset('images/logo.png?v=20261118b') }}" alt="" class="w-11 h-11 rounded-xl bg-slate-100 object-contain p-1 ring-1 ring-slate-200/80 shadow-sm shadow-slate-900/10">
    <span class="font-bold tracking-tight text-[17px] {{ $dark ? 'text-white' : 'text-slate-900' }}">{{ config('app.name') }}</span>
</span>
