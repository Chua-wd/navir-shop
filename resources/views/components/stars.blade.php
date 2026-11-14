{{-- Star rating, e.g. <x-stars :rating="4.3" /> shows ★★★★☆ with the 5th star 30% filled --}}
@props(['rating' => 0, 'size' => 14])

@php $percent = max(0, min(100, $rating / 5 * 100)); @endphp

<span {{ $attributes->class('stars') }} role="img" aria-label="{{ number_format($rating, 1) }} out of 5 stars" style="font-size: {{ $size }}px;">
    <span class="stars-empty" aria-hidden="true">★★★★★</span>
    <span class="stars-full" aria-hidden="true" style="width: {{ $percent }}%;">★★★★★</span>
</span>
