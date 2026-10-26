<div data-content-preview {{ $attributes->class(['relative']) }}>
    <div data-preview-content>{{ $slot }}</div>
    <x-loading-skeleton data-preview-placeholder class="absolute inset-0 overflow-hidden rounded-card bg-surface p-3" />
</div>
