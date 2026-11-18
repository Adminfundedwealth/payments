<header class="public-nav sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-slate-200/70 transition-shadow" id="site-header">
    <div class="public-nav-inner mx-auto max-w-6xl px-4 sm:px-6 h-16 flex items-center justify-between">
        <a href="{{ route('home') }}"><x-logo /></a>
        <nav class="public-nav-links hidden md:flex items-center gap-8 text-sm font-medium text-slate-600" aria-label="Main navigation">
            <a href="{{ route('home') }}#features" class="hover:text-slate-900 transition-colors">Features</a>
            <a href="{{ route('home') }}#how" class="hover:text-slate-900 transition-colors">How it works</a>
            <a href="{{ route('home') }}#pricing" class="hover:text-slate-900 transition-colors">Pricing</a>
            <a href="{{ route('docs') }}" class="hover:text-slate-900 transition-colors">Developers</a>
        </nav>
        <div class="flex items-center gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard <x-icon name="arrow-right" class="w-4 h-4" /></a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost hidden sm:inline-flex">Log in</a>
                @if (config('auth.allow_registration', false))
                    <a href="{{ route('register') }}" class="btn btn-primary hidden sm:inline-flex">Get started <x-icon name="arrow-right" class="w-4 h-4" /></a>
                @endif
            @endauth
            <button type="button" class="public-menu-toggle btn btn-ghost p-2 md:hidden" data-public-nav-toggle aria-label="Open navigation menu" aria-expanded="false" aria-controls="public-mobile-menu">
                <x-icon name="menu" class="w-5 h-5" />
            </button>
        </div>
    </div>
    <nav id="public-mobile-menu" class="public-mobile-menu hidden md:hidden" aria-label="Mobile navigation">
        <a href="{{ route('home') }}#features">Features</a>
        <a href="{{ route('home') }}#how">How it works</a>
        <a href="{{ route('home') }}#pricing">Pricing</a>
        <a href="{{ route('docs') }}">Developers</a>
        @guest
            <a href="{{ route('login') }}">Log in</a>
            @if (config('auth.allow_registration', false))
                <a href="{{ route('register') }}">Get started</a>
            @endif
        @else
            <a href="{{ route('dashboard') }}">Dashboard</a>
        @endguest
    </nav>
</header>
<script nonce="{{ $csp_nonce }}">
    const hdr = document.getElementById('site-header');
    window.addEventListener('scroll', () => hdr.classList.toggle('shadow-sm', window.scrollY > 10), { passive: true });
</script>
