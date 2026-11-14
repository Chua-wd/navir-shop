{{-- Navbar component: used on every page via <x-navbar />.
     Desktop: all links in the bar. Phone (900px and below): logo, Cart and a ☰ button
     that opens a panel with Shop / About / Contact and the account links. --}}
@php
    $cartCount = \App\Support\Cart::count();
    $user = Auth::user();
    $firstName = $user ? \Illuminate\Support\Str::of($user->name)->before(' ') : null;

    // Account links (shown in the bar on desktop and in the ☰ panel on phones)
    $accountLinks = $user
        ? array_filter([
            ['url' => route('orders.index'), 'label' => 'My orders', 'active' => request()->is('orders*')],
            $user->isAdmin()
                ? ['url' => route('dashboard'), 'label' => "Dashboard · {$firstName}", 'active' => request()->is('dashboard*')]
                : ['url' => route('profile.edit'), 'label' => "Profile · {$firstName}", 'active' => request()->is('profile')],
        ])
        : [
            ['url' => route('login'), 'label' => 'Log in', 'active' => request()->is('login')],
            ['url' => route('register'), 'label' => 'Sign up', 'active' => request()->is('register')],
        ];
    $pageLinks = [
        ['url' => url('/products'), 'label' => 'Shop', 'active' => request()->is('products*')],
        ['url' => url('/about'), 'label' => 'About', 'active' => request()->is('about')],
        ['url' => url('/contact'), 'label' => 'Contact', 'active' => request()->is('contact')],
    ];
@endphp

<header class="site-header">
    <nav class="container nav" aria-label="Main">
        <div class="nav-group">
            <a href="{{ url('/') }}" class="brand">
                <x-application-logo class="brand-mark" />
                NAVIR
            </a>
            <span class="nav-links" style="display: flex; gap: 2px;">
                @foreach ($pageLinks as $link)
                    <a href="{{ $link['url'] }}" class="{{ $link['active'] ? 'active' : '' }}">{{ $link['label'] }}</a>
                @endforeach
            </span>
        </div>

        <div class="nav-group">
            <a href="{{ url('/cart') }}" class="{{ request()->is('cart') ? 'active' : '' }}">
                Cart <span class="count">{{ $cartCount }}</span>
            </a>

            {{-- Login state (Laravel Breeze). Hidden on phones: they're in the ☰ panel instead. --}}
            <span class="nav-account">
                @foreach ($accountLinks as $link)
                    <a href="{{ $link['url'] }}" class="{{ $link['active'] ? 'active' : '' }}">{{ $link['label'] }}</a>
                @endforeach
                @auth
                    <form method="POST" action="{{ route('logout') }}" style="display: contents;">
                        @csrf
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">Log out</a>
                    </form>
                @endauth
            </span>

            <button type="button" class="menu-btn" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu">
                <span aria-hidden="true">☰</span>
            </button>
        </div>
    </nav>

    {{-- Phone menu panel --}}
    <div id="mobile-menu" class="container mobile-menu" hidden>
        <div class="mobile-menu-card">
            @foreach ($pageLinks as $link)
                <a href="{{ $link['url'] }}" class="{{ $link['active'] ? 'active' : '' }}">{{ $link['label'] }}</a>
            @endforeach
            <hr>
            @foreach ($accountLinks as $link)
                <a href="{{ $link['url'] }}" class="{{ $link['active'] ? 'active' : '' }}">{{ $link['label'] }}</a>
            @endforeach
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Log out</button>
                </form>
            @endauth
        </div>
    </div>

    <script>
        // ☰ opens / closes the phone menu (Escape closes it too)
        (function () {
            var button = document.currentScript.parentElement.querySelector('.menu-btn');
            var menu = document.getElementById('mobile-menu');
            function toggle(open) {
                menu.hidden = !open;
                button.setAttribute('aria-expanded', open ? 'true' : 'false');
                button.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
                button.firstElementChild.textContent = open ? '✕' : '☰';
            }
            button.addEventListener('click', function () { toggle(menu.hidden); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { toggle(false); button.focus(); } });
        })();
    </script>
</header>
