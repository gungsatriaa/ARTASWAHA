<x-layouts.app :learning-paths="$learningPaths" title="Profil — ARTASWAHA">
    <main class="mx-auto max-w-3xl px-6 py-12 sm:py-16 lg:px-10">
        <section class="rounded-card bg-surface p-7 sm:p-10" aria-labelledby="profile-title">
            <p class="font-semibold text-accent">Akun saya</p>
            <h1 id="profile-title" class="mt-2 font-display text-3xl font-bold sm:text-4xl">Profil</h1>
            <div class="mt-8 flex items-center gap-5 rounded-3xl bg-background p-5 sm:p-6">
                <span class="grid size-16 shrink-0 place-items-center rounded-full bg-accent font-display text-2xl font-extrabold text-background" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <h2 class="truncate font-display text-xl font-bold">{{ auth()->user()->name }}</h2>
                    <p class="mt-1 truncate font-readable text-foreground/65">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-3xl bg-background p-5">
                    <dt class="text-sm font-semibold text-foreground/55">Nama</dt>
                    <dd class="mt-2 font-semibold">{{ auth()->user()->name }}</dd>
                </div>
                <div class="rounded-3xl bg-background p-5">
                    <dt class="text-sm font-semibold text-foreground/55">Email</dt>
                    <dd class="mt-2 break-all font-semibold">{{ auth()->user()->email }}</dd>
                </div>
            </dl>
        </section>
    </main>
</x-layouts.app>
