@props(['src', 'label' => 'Dengarkan materi'])
<div data-content-audio class="min-w-0 rounded-card bg-background p-4">
    <p class="text-sm font-bold text-accent">{{ $label }}</p>
    <audio controls preload="none" src="{{ $src }}" aria-label="{{ $label }}" class="mt-3 block w-full min-w-0 max-w-full">Browser ini tidak mendukung pemutaran audio.</audio>
    <button type="button" data-audio-stop class="mt-2 inline-flex min-h-11 items-center rounded-full border border-white/15 px-4 text-xs font-bold">Hentikan audio</button>
    <p data-audio-error role="status" class="mt-1 text-xs leading-6 text-foreground/60"></p>
</div>
