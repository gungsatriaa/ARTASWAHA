<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#000000">
        <title>{{ $lesson->title }} — ARTASWAHA</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="border-b border-white/10">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-5">
                <a href="{{ route('home') }}" class="font-display text-xl font-bold">ARTASWAHA</a>
                <a href="{{ route('learning.paths.show', $lesson->learningPath->slug) }}" class="rounded-full px-4 py-2 text-sm font-semibold hover:bg-surface">Daftar pelajaran</a>
            </div>
        </header>
        <main class="mx-auto max-w-3xl px-6 py-12 sm:py-16">
            <p class="font-semibold text-accent">{{ $lesson->learningPath->title }}</p>
            <h1 class="mt-2 font-display text-4xl font-bold sm:text-5xl">{{ $lesson->title }}</h1>
            <p class="mt-4 font-readable text-lg leading-8 text-foreground/70">{{ $lesson->summary }}</p>

            @if (session('status'))
                <p role="status" class="mt-8 rounded-2xl bg-primary/20 p-4 font-semibold text-accent">{{ session('status') }}</p>
            @endif

            <article class="mt-8 rounded-card bg-surface p-6 font-readable text-lg leading-8 sm:p-8">
                {{ $lesson->content }}
            </article>

            @if ($progress?->completed_at)
                <p class="mt-6 rounded-2xl bg-surface px-5 py-4 font-semibold text-accent">Pelajaran selesai · {{ $progress->points }} poin</p>
            @else
                <form method="POST" action="{{ route('learning.progress.store', $lesson->slug) }}" class="mt-8">
                    @csrf
                    <button class="min-h-14 w-full rounded-full bg-primary px-7 py-4 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" type="submit">Tandai pelajaran selesai</button>
                </form>
            @endif
        </main>
    </body>
</html>
