@props(['learningPaths', 'user', 'active' => 'progress'])

<x-layouts.app :learning-paths="$learningPaths" :show-footer="false" :title="$active === 'about' ? 'Tentang ARTASWAHA' : 'Profil — ARTASWAHA'">
    <div class="mx-auto grid min-h-[calc(100svh-6rem)] max-w-7xl gap-5 px-6 py-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-center lg:px-10">
        <aside class="rounded-card bg-surface p-5 sm:p-6" aria-label="Profil dan navigasi akun">
            <div class="flex items-center gap-4 lg:flex-col lg:items-start">
                <span class="grid size-16 shrink-0 place-items-center rounded-full bg-accent font-display text-2xl font-extrabold text-background" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <h1 class="break-words font-display text-xl font-bold">{{ $user->name }}</h1>
                    <p class="mt-1 break-all text-sm text-foreground/55">{{ $user->email }}</p>
                </div>
            </div>

            <nav class="mt-6 grid gap-2 border-t border-white/10 pt-5" aria-label="Menu profil">
                <a href="{{ route('profile.show') }}" @class(['flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-accent', 'bg-primary/15 text-accent' => $active === 'progress', 'text-foreground/75 hover:bg-background hover:text-accent' => $active !== 'progress'])>
                    <span aria-hidden="true">✦</span> Progres belajar
                </a>
                <a href="{{ route('profile.about') }}" @class(['flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-accent', 'bg-primary/15 text-accent' => $active === 'about', 'text-foreground/75 hover:bg-background hover:text-accent' => $active !== 'about'])>
                    <span aria-hidden="true">ⓘ</span> Tentang aplikasi ini
                </a>
                <a href="{{ route('quizzes.index') }}" class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-foreground/75 transition hover:bg-background hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">
                    <span aria-hidden="true">♫</span> Kuis saya
                </a>
            </nav>
        </aside>

        <div class="min-w-0">{{ $slot }}</div>
    </div>
</x-layouts.app>
