@props(['illustration' => false])

<button type="button" data-mascot {{ $attributes->class(['mascot-trigger rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent']) }} aria-label="Sapa maskot ARTASWAHA">
    <img src="{{ asset($illustration ? 'image/asset1.webp' : 'image/maskot.webp') }}" alt="" class="size-full object-contain" @if ($illustration) data-load-critical fetchpriority="high" @else width="48" height="48" @endif>
</button>
<span data-mascot-message class="mascot-message" role="status" hidden>Om Swastyastu! Siap belajar hari ini?</span>
