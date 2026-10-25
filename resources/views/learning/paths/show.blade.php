<x-layouts.app :show-footer="false" :title="$learningPath->title . ' — ARTASWAHA'">
    <div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-9 lg:px-8">
        <a href="{{ route('home') }}" class="mb-5 inline-flex min-h-10 items-center gap-2 rounded-full text-sm font-semibold text-foreground/65 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
            <span aria-hidden="true">←</span> Semua materi
        </a>

        @if (session('status'))
            <x-flash-notice class="mb-5">{{ session('status') }}</x-flash-notice>
        @endif

        <header class="rounded-card border border-white/10 bg-surface p-5 sm:p-7 lg:p-9">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full bg-accent/10 px-3 py-1.5 text-sm font-bold text-accent">
                        <span aria-hidden="true">{{ $learningPath->group === 'paribasa' ? '✦' : 'ᬅ' }}</span>
                        {{ $learningPath->group === 'paribasa' ? 'Paribasa Bali' : 'Aksara Bali' }}
                    </span>
                    <h1 class="mt-4 break-words font-display text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">{{ $learningPath->title }}</h1>
                    @if ($learningPath->description)
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-foreground/65 sm:text-base">{{ $learningPath->description }}</p>
                    @endif
                </div>
                <p class="w-fit shrink-0 rounded-full border border-white/10 bg-background px-4 py-2 text-sm font-bold text-foreground/75">{{ $learningPath->lessons->count() }} pelajaran</p>
            </div>

            @if ($profile)
                <div class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                    @if (str_starts_with($profile->avatar ?? '', 'avatars/'))
                        <img src="{{ route('profile.avatar.show') }}" alt="" class="size-10 shrink-0 rounded-full object-cover">
                    @elseif ($profile->avatar)
                        <img src="{{ asset($profile->avatar) }}" alt="" class="size-10 shrink-0 rounded-full object-cover">
                    @endif
                    <p class="min-w-0 text-sm text-foreground/65"><span class="font-bold text-foreground">Halo, {{ $profile->name }}!</span> Siap lanjut belajar?</p>
                </div>
            @endif
        </header>

        <section class="mt-8 sm:mt-10" aria-labelledby="lesson-list-title">
            <div class="mb-5">
                <h2 id="lesson-list-title" class="font-display text-2xl font-bold sm:text-3xl">Pilih pelajaran</h2>
                <p class="mt-1 text-sm leading-6 text-foreground/60">Pelajari materi, lalu coba latihan singkatnya.</p>
            </div>

            @if ($learningPath->lessons->isNotEmpty())
                <ol class="grid gap-3 md:grid-cols-2">
                    @foreach ($learningPath->lessons as $lesson)
                        <li>
                            <a href="{{ route('learning.lessons.show', $lesson->slug) }}" class="group flex h-full min-h-24 items-start gap-3 rounded-2xl border border-white/10 bg-surface p-4 transition-colors hover:border-accent/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:gap-4 sm:p-5">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent/10 font-display text-sm font-bold text-accent" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block break-words font-display text-lg font-bold leading-snug text-foreground group-hover:text-accent">{{ $lesson->title }}</span>
                                    @if ($lesson->summary)
                                        <span class="mt-1 block break-words text-sm leading-6 text-foreground/60">{{ $lesson->summary }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-xl text-foreground/45 group-hover:text-accent" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="flex flex-col gap-4 rounded-card border border-white/10 bg-surface p-5 sm:flex-row sm:items-center sm:gap-5 sm:p-6">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent/10 text-xl text-accent" aria-hidden="true">✦</span>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-display text-xl font-bold">Pelajaran sedang disiapkan</h3>
                        <p class="mt-1 max-w-xl text-sm leading-6 text-foreground/60">Belum ada pelajaran yang tersedia untuk {{ $learningPath->title }}. Sementara itu, kamu bisa mencoba materi lain.</p>
                    </div>
                    <a href="{{ route('home') }}" class="inline-flex min-h-11 w-full shrink-0 items-center justify-center rounded-full border border-white/15 px-5 text-sm font-bold text-foreground transition hover:border-accent/60 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:w-auto">Lihat materi lain</a>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
