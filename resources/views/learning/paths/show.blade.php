<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#141618">
        <title>{{ $learningPath->title }} — ARTASWAHA</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="border-b border-white/10">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
                <a href="{{ route('home') }}" class="font-display text-xl font-bold">ARTASWAHA</a>
                <a href="{{ route('home') }}#jalur" class="rounded-full px-4 py-2 text-sm font-semibold hover:bg-surface">Kembali</a>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-6 py-12 sm:py-16">
            <p class="font-semibold text-accent">{{ $learningPath->group === 'paribasa' ? 'Paribasa Bali' : 'Aksara Bali' }}</p>
            <h1 class="mt-2 font-display text-4xl font-bold sm:text-5xl">{{ $learningPath->title }}</h1>
            <p class="mt-4 max-w-2xl font-readable text-lg leading-8 text-foreground/70">{{ $learningPath->description }}</p>

            <section class="mt-10" aria-labelledby="lesson-list-title">
                <h2 id="lesson-list-title" class="font-display text-2xl font-bold">Pelajaran</h2>
                @forelse ($learningPath->lessons as $lesson)
                    <a href="{{ route('learning.lessons.show', $lesson->slug) }}" class="mt-4 flex items-center justify-between gap-4 rounded-card bg-surface p-5 transition hover:bg-surface/80 sm:p-6">
                        <span>
                            <span class="block font-bold">{{ $lesson->title }}</span>
                            <span class="mt-1 block font-readable text-sm text-foreground/65">{{ $lesson->summary }}</span>
                        </span>
                        <span class="text-accent" aria-hidden="true">→</span>
                    </a>
                @empty
                    <p class="mt-4 rounded-card bg-surface p-6 font-readable leading-7 text-foreground/65">Materi untuk bagian ini sedang disiapkan dan akan ditampilkan setelah ditinjau.</p>
                @endforelse
            </section>
        </main>
    </body>
</html>
