<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $quiz->title }} — ARTASWAHA</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="border-b border-white/10"><div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-5"><a href="{{ route('quizzes.index') }}" class="font-display text-xl font-bold">ARTASWAHA / Kuis</a><a href="{{ route('quizzes.index') }}" class="text-sm font-semibold text-accent">Kembali</a></div></header>
        <main class="mx-auto max-w-4xl px-6 py-10">
            @if (session('status'))<p role="status" class="mb-6 rounded-2xl bg-primary/20 p-4 text-accent">{{ session('status') }}</p>@endif
            <p class="text-sm font-semibold text-accent">Kuis pribadi</p>
            <h1 class="mt-2 font-display text-4xl font-bold">{{ $quiz->title }}</h1>
            @if ($quiz->description)<p class="mt-3 font-readable text-foreground/65">{{ $quiz->description }}</p>@endif
            <p class="mt-5 text-sm text-foreground/50">{{ $quiz->questions->count() }} pertanyaan</p>
            <form method="POST" action="{{ route('rooms.store', $quiz) }}" class="mt-8">
                @csrf
                <button class="min-h-12 rounded-full bg-primary px-7 py-3 font-bold text-white">Buat room dari kuis ini</button>
            </form>
            <ol class="mt-10 space-y-4">
                @foreach ($quiz->questions as $index => $question)
                    <li class="rounded-card bg-surface p-5"><span class="text-sm font-semibold text-accent">Pertanyaan {{ $index + 1 }}</span><p class="mt-2 font-semibold">{{ $question->prompt }}</p></li>
                @endforeach
            </ol>
        </main>
    </body>
</html>
