{{--
    Layout for Breeze guest pages (log in, sign up, forgot password, ...).
    Used via <x-guest-layout> ... </x-guest-layout>.
    Left: navy panel in the style of the home hero, with the serum photo. Right: the form.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Account' }} · Navir Men's Skincare</title>

    @include('layouts.partials.styles')
</head>
<body>
    <div class="auth">
        <aside class="auth-side">
            <a href="{{ url('/') }}" class="brand">
                <x-application-logo class="brand-mark" />
                NAVIR
            </a>

            {{-- Serum photo with a transparent background (public/images/login-sign-up.webp) --}}
            <img src="{{ asset('images/login-sign-up.webp') }}" width="1024" height="1536" class="auth-photo" fetchpriority="high"
                 alt="NAVIR Niacinamide 10% Serum bottle with water drops">

            <div style="position: relative;">
                <h1 class="display">A men's routine,<br>made simple.</h1>
                <ul>
                    <li>Dermatologist tested</li>
                    <li>3-step routine</li>
                    <li>Made for humid climates</li>
                </ul>
            </div>
        </aside>

        <main class="auth-main">
            <div class="auth-card">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
