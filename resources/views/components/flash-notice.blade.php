@props(['tone' => 'success'])

<div role="status" {{ $attributes->class([
    'flex items-start gap-3 rounded-2xl border border-l-4 bg-surface px-4 py-3 text-sm font-semibold leading-6 text-foreground',
    'border-success/25 border-l-success' => $tone === 'success',
    'border-primary/30 border-l-primary' => $tone === 'info',
]) }}>
    <span @class([
        'grid size-8 shrink-0 place-items-center rounded-full text-base font-bold',
        'bg-success/10 text-success' => $tone === 'success',
        'bg-primary/20 text-accent' => $tone === 'info',
    ]) aria-hidden="true">{{ $tone === 'success' ? '✓' : 'i' }}</span>
    <span class="min-w-0 pt-1">{{ $slot }}</span>
</div>
