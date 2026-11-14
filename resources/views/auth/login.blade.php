<x-guest-layout>
    <x-slot name="title">Log in</x-slot>

    <p class="eyebrow">( log in )</p>
    <h2 class="display display-lg" style="margin-bottom: 24px;">Welcome back.</h2>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="field">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="field">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="auth-links" style="margin: 0 0 20px;">
            <!-- Remember Me -->
            <label for="remember_me" class="check">
                <input id="remember_me" type="checkbox" name="remember">
                Remember me
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Forgot password?</a>
            @endif
        </div>

        <x-primary-button class="btn-block">Log in</x-primary-button>
    </form>

    <p class="auth-links" style="justify-content: center;">
        <span class="muted">Don't have an account? <a href="{{ route('register') }}" style="color: var(--ink);">Sign up</a></span>
    </p>

    {{-- Public demo accounts (config/shop.php 'demo'): one click fills the form --}}
    @if ($demo = config('shop.demo'))
        <div class="demo-box">
            <p class="eyebrow" style="margin: 0 0 4px;">( try the demo )</p>
            <p class="muted" style="margin: 0 0 12px; font-size: 13px;">
                Password for both: <code>{{ $demo['password'] }}</code>. Shared accounts, so saving changes is turned off.
            </p>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @foreach ($demo['accounts'] as $email => $account)
                    <button type="button" class="btn btn-ghost btn-sm demo-fill" data-email="{{ $email }}" data-password="{{ $demo['password'] }}">
                        {{ $account['role'] === 'user' ? 'Shopper' : (\App\Models\User::ROLES[$account['role']] ?? $account['role']) }}
                        <span class="hint" style="margin-left: 4px;">{{ $email }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        <script>
            // Fill the login form with a demo account
            document.querySelectorAll('.demo-fill').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('email').value = button.dataset.email;
                    document.getElementById('password').value = button.dataset.password;
                    document.getElementById('password').focus();
                });
            });
        </script>
    @endif
</x-guest-layout>
