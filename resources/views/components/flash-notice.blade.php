@props(['tone' => 'success'])

<div role="status" {{ $attributes->class([
    'flex items-start gap-3 rounded-card px-5 py-3.5 text-sm font-semibold leading-6',
    'bg-[#15803d] text-white' => $tone === 'success',
    'border border-l-4 border-primary/30 border-l-primary bg-surface text-foreground' => $tone === 'info',
]) }}>
    <span @class([
        'grid size-8 shrink-0 place-items-center rounded-full text-base font-bold',
        'text-white' => $tone === 'success',
        'bg-primary/20 text-accent' => $tone === 'info',
    ]) aria-hidden="true">
        @if ($tone === 'success')
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6" /></svg>
        @else
            i
        @endif
    </span>
    <span class="min-w-0 pt-1">{{ $slot }}</span>
</div>
