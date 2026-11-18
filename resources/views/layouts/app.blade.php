@extends('layouts.base')

@section('body')
@php
    $user = auth()->user();
    $reviewCount = $user->orders()->where('status', \App\Models\Order::REVIEW)->count();
    $sections = [
        'Payments' => [
            ['dashboard', 'Dashboard', 'home', 'dashboard'],
            ['orders.index', 'Orders', 'receipt', 'orders.*'],
            ['accounts.index', 'UPI Accounts', 'qr', 'accounts.*'],
        ],
        'Developers' => [
            ['settings', 'API & Webhooks', 'key', 'settings*'],
            ['docs', 'API Docs', 'book', 'docs'],
        ],
    ];
    if ($user->isAdmin()) {
        $sections['Admin'] = [
            ['admin.merchants', 'Merchants', 'users', 'admin.merchants*'],
            ['admin.orders', 'All Orders', 'chart', 'admin.orders'],
            ['admin.transactions.index', 'Transaction Debugger', 'search', 'admin.transactions.*'],
        ];
    }
@endphp
<div class="app-shell min-h-full">
    {{-- Mobile top bar --}}
    <div class="app-header app-mobile-header lg:hidden sticky top-0 z-30 flex items-center justify-between bg-white/90 backdrop-blur border-b border-slate-200 px-4 h-14">
        <a href="{{ route('dashboard') }}"><x-logo /></a>
        <button type="button" data-sidebar-toggle class="btn btn-ghost p-2" aria-label="Open menu" aria-expanded="false" aria-controls="sidebar">
            <x-icon name="menu" class="w-6 h-6" />
        </button>
    </div>
    <div id="backdrop" data-sidebar-close class="app-backdrop hidden lg:hidden fixed inset-0 z-30 bg-slate-900/40" aria-hidden="true"></div>

    {{-- Sidebar --}}
    <aside id="sidebar" class="app-sidebar fixed inset-y-0 left-0 z-40 w-64 -translate-x-full lg:translate-x-0 transition-transform bg-white border-r border-slate-200 flex flex-col" aria-label="Main navigation">
        <div class="app-sidebar-brand h-16 flex items-center px-5 border-b border-slate-100">
            <a href="{{ route('dashboard') }}"><x-logo /></a>
        </div>

        <div class="app-sidebar-action px-3 pt-4">
            <a href="{{ route('orders.create') }}" class="btn btn-primary w-full"><x-icon name="plus" class="w-4 h-4" /> New payment link</a>
        </div>

        <nav class="app-sidebar-nav flex-1 overflow-y-auto px-3 py-4 space-y-6">
            @foreach ($sections as $heading => $items)
                <div>
                    <div class="app-nav-heading px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $heading }}</div>
                    <div class="space-y-0.5">
                        @foreach ($items as [$route, $label, $icon, $pattern])
                            @php $active = request()->routeIs($pattern); @endphp
                            <a href="{{ route($route) }}" @class([
                                'app-nav-link group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                'app-nav-link-active' => $active,
                            ]) @if ($active) aria-current="page" @endif>
                                <x-icon :name="$icon" class="app-nav-icon w-5 h-5" />
                                <span class="flex-1">{{ $label }}</span>
                                @if ($route === 'orders.index' && $reviewCount)
                                    <span class="rounded-full bg-sky-500 px-1.5 text-[11px] font-semibold text-white tabular-nums" title="Payments waiting for your review">{{ $reviewCount }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="app-sidebar-footer border-t border-slate-100 p-3">
            <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                <x-avatar :name="$user->displayName()" />
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-slate-900 truncate">{{ $user->displayName() }}</div>
                    <div class="text-xs text-slate-500 truncate">{{ $user->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Log out" aria-label="Log out"><x-icon name="logout" class="w-5 h-5" /></button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="app-content lg:pl-64">
        <header class="app-topbar hidden lg:flex items-center justify-between px-8 xl:px-10 py-3">
            <div class="app-topbar-context text-sm font-medium">Merchant workspace</div>
            <div class="app-topbar-actions flex items-center gap-3">
                <a href="{{ route('orders.index', ['status' => 'review']) }}" class="app-notification relative grid place-items-center w-10 h-10 rounded-xl" aria-label="Notifications: {{ $reviewCount }} payments waiting for review" title="{{ $reviewCount }} payments waiting for review">
                    <x-icon name="bell" class="w-5 h-5" />
                    @if ($reviewCount)
                        <span class="app-notification-badge absolute -right-1 -top-1 min-w-5 rounded-full px-1 text-center text-[10px] font-bold text-white tabular-nums">{{ $reviewCount }}</span>
                    @endif
                </a>
                <a href="{{ route('settings') }}" class="app-profile flex items-center gap-3 rounded-xl px-2.5 py-1.5" aria-label="Account settings">
                    <x-avatar :name="$user->displayName()" size="w-9 h-9" />
                    <span class="app-profile-copy text-left">
                        <span class="block text-sm font-semibold">{{ $user->displayName() }}</span>
                        <span class="block text-xs">{{ $user->email }}</span>
                    </span>
                </a>
            </div>
        </header>
        <main class="app-main mx-auto max-w-7xl px-4 sm:px-6 lg:px-10 py-6 lg:py-10">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
                <div class="min-w-0">
                    @hasSection('back')
                        <a href="@yield('back')" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 mb-2"><x-icon name="arrow-left" class="w-4 h-4" /> Back</a>
                    @endif
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 truncate">@yield('title')</h1>
                    @hasSection('subtitle')<p class="mt-1 text-sm text-slate-500">@yield('subtitle')</p>@endif
                </div>
                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">@yield('actions')</div>
            </div>

            @if (session('status'))
                <div class="flash-message flash-success mb-6 flex items-start gap-3 rounded-xl bg-emerald-50 ring-1 ring-emerald-200 text-emerald-800 px-4 py-3 text-sm" role="status">
                    <x-icon name="check-circle" class="w-5 h-5 text-emerald-600" /><div>{{ session('status') }}</div>
                </div>
            @endif
            @if ($errors->any())
                <div class="flash-message flash-error mb-6 flex items-start gap-3 rounded-xl bg-red-50 ring-1 ring-red-200 text-red-800 px-4 py-3 text-sm" role="alert">
                    <x-icon name="alert" class="w-5 h-5 text-red-600" />
                    <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@endsection
