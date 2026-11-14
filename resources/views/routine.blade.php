@extends('layouts.app')

@section('title', 'Find my routine')

@section('content')
    <p class="eyebrow">( find my routine )</p>
    <h1 class="display display-lg">Your 3-step routine in 1 minute.</h1>
    <p class="muted" style="margin: 8px 0 24px; max-width: 560px;">
        Four quick questions about your skin. We match them to a Cleanse &rarr; Treat &rarr; Protect routine from our range.
        No photos, no sign-up.
    </p>

    {{-- After "Add to cart" on a single product --}}
    @if ($added = session('added'))
        <div class="alert" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span>Added to cart: {{ $added['name'] }} &times; {{ $added['quantity'] }}</span>
            <a href="{{ route('cart.index') }}" class="btn btn-sm">View cart</a>
        </div>
    @endif
    @error('quantity')
        <div class="alert alert-error">{{ $message }}</div>
    @enderror

    @if ($routine)
        {{-- ===== Result ===== --}}
        <section class="routine-result" id="result">
            <div class="routine-head">
                <div>
                    <p class="eyebrow" style="margin: 0;">( your routine )</p>
                    <div class="chips" style="margin-top: 8px;">
                        @foreach (explode(' · ', $routine['summary']) as $label)
                            <span class="chip">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
                <a href="{{ route('routine.show') }}" class="btn btn-ghost btn-sm">Retake the quiz</a>
            </div>

            <ol class="routine-steps">
                @foreach ($routine['steps'] as $i => $step)
                    <li class="pill routine-step">
                        <div class="routine-step-head">
                            <span class="routine-n">{{ $i + 1 }}</span>
                            <div>
                                <div class="display display-md">{{ $step['title'] }}</div>
                                <div class="hint">{{ $step['when'] }}</div>
                            </div>
                        </div>
                        @foreach ($step['items'] as $item)
                            <x-routine-item :product="$item['product']" :why="$item['why']" />
                        @endforeach
                    </li>
                @endforeach
            </ol>

            {{-- Add everything in steps 1-3 to the cart --}}
            <form method="POST" action="{{ route('routine.cart') }}" class="pill routine-buy">
                @csrf
                @foreach ($answers as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <div>
                    <div class="hint">{{ $routine['products']->count() }} products &middot; full routine</div>
                    <div class="display display-md">Rp {{ number_format($routine['total']) }}</div>
                </div>
                <button type="submit" class="btn">Add the whole routine to cart</button>
            </form>

            @if ($routine['extras'])
                <h2 class="display display-md" style="margin: 32px 0 12px;">Good extras for you</h2>
                <div class="routine-extras">
                    @foreach ($routine['extras'] as $item)
                        <div class="pill" style="padding: 16px;">
                            <x-routine-item :product="$item['product']" :why="$item['why']" />
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="hint" style="margin-top: 20px;">
                This is a general guide, not medical advice. Got a skin condition? Ask a dermatologist first.
            </p>
        </section>
    @else
        {{-- ===== Questions ===== --}}
        @if ($missing)
            <div class="alert alert-error">Please answer: {{ collect($missing)->pluck('title')->implode(' / ') }}</div>
        @endif

        <form method="GET" action="{{ route('routine.show') }}" class="routine-quiz">
            @foreach ($questions as $key => $question)
                <fieldset class="pill routine-question">
                    <legend><span class="routine-n">{{ $loop->iteration }}</span> {{ $question['title'] }}</legend>
                    <div class="routine-options">
                        @foreach ($question['options'] as $value => [$text])
                            <label class="routine-option">
                                <input type="radio" name="{{ $key }}" value="{{ $value }}" required @checked(($answers[$key] ?? null) === $value)>
                                <span>{{ $text }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            <button type="submit" class="btn" style="width: 100%; max-width: 360px;">Show my routine</button>
        </form>
    @endif
@endsection
