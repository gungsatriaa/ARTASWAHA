<x-layouts.app :learning-paths="$learningPaths" :mobile-dashboard="$showMobileDashboard" :mobile-learning-path="$featuredPath" :canonical="route('home')">
            @if (session('status'))
                <div class="mx-auto max-w-7xl px-5 pt-6 md:px-6 lg:px-10">
                    <x-flash-notice>{{ session('status') }}</x-flash-notice>
                </div>
            @endif
            @if ($showMobileDashboard)
                <x-mobile-learning-dashboard :user="$user" :profile="$profile" :dashboard-paths="$dashboardPaths" :last-learning-progress="$lastLearningProgress" :analysis="$analysis" />
            @endif
            <div @class(['hidden md:block' => $showMobileDashboard])>
            <section class="mx-auto grid min-h-[calc(100svh-6rem)] max-w-7xl items-center gap-10 overflow-x-clip px-5 pb-12 pt-8 md:gap-12 md:px-6 md:pb-16 md:pt-16 lg:grid-cols-[1.05fr_.95fr] lg:px-10">
                <div class="max-w-2xl">
                    <h1 class="font-display text-5xl font-bold leading-[1.08] tracking-tight sm:text-6xl lg:text-7xl">Bahasa Bali jadi <span class="text-accent">seru</span> dipelajari.</h1>
                    <p class="mt-6 max-w-xl font-readable text-lg leading-8 text-foreground/75 sm:text-xl">Kenali paribasa dan aksara Bali lewat cerita, latihan singkat, dan permainan yang bisa langsung dicoba. Mulai tanpa akun; progres tersimpan di perangkatmu.</p>
                    <div id="mulai" class="mt-9 flex flex-wrap gap-3">
                        @if ($hasCompletedInterview)
                            <button type="button" data-learning-picker-open aria-haspopup="dialog" aria-controls="learning-picker" class="inline-flex min-h-14 items-center justify-center rounded-full bg-primary px-8 py-4 font-bold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Mulai belajar</button>
                        @else
                            <a href="{{ route('learning.interview.show') }}" class="inline-flex min-h-14 items-center justify-center rounded-full bg-primary px-8 py-4 font-bold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Mulai belajar gratis</a>
                        @endif
                        <a href="{{ route('quizzes.index') }}" class="inline-flex min-h-14 items-center justify-center rounded-full border border-white/15 px-7 py-4 font-bold transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Gabung kuis</a>
                    </div>
                    <p class="mt-4 text-sm text-foreground/55">Belajar dan gabung kuis tanpa login dengan profil anonim. Masuk atau daftar jika ingin membuat kuis.</p>
                    @unless ($hasCompletedInterview)
                        <button type="button" data-learning-picker-open aria-haspopup="dialog" aria-controls="learning-picker" class="mt-2 rounded-full text-sm font-bold text-accent transition hover:underline focus-visible:outline-2 focus-visible:outline-accent">Atau lihat semua materi</button>
                    @endunless
                </div>

                <div class="mx-auto w-full max-w-lg py-4 lg:pl-8">
                    <div class="relative rounded-card bg-surface p-7 sm:p-9">
                        <div class="flex items-center gap-3 text-sm font-semibold text-accent">
                            <span class="font-balinese text-xl" lang="ban" aria-hidden="true">ᬅ</span>
                            <span>Belajar Aksara Bali</span>
                        </div>
                        <div class="mt-5 grid items-center gap-2 sm:relative sm:grid-cols-[minmax(0,1fr)_11rem]">
                            <div class="relative z-10 min-w-0 py-3">
                                <p class="font-balinese text-2xl leading-[1.8] text-foreground sm:text-3xl" lang="ban">ᬒᬁ ᬲ᭄ᬯᬲ᭄ᬢ᭄ᬬᬲ᭄ᬢᬸ᭟</p>
                                <p class="mt-3 font-display text-xl font-bold sm:text-2xl">Om Swastyastu</p>
                                <p class="mt-2 font-readable leading-7 text-foreground/65">Salam pembuka dalam bahasa Bali. Kenali bentuk aksaranya lewat contoh.</p>
                            </div>
                            <img src="{{ asset('image/asset1.webp') }}" alt="Maskot ARTASWAHA sedang belajar menulis" class="absolute right-0 top-12 z-0 h-40 w-40 scale-[1.4] max-[380px]:scale-[1.0] max-[380px]:translate-x-8 translate-x-4 -translate-y-15 object-contain sm:absolute sm:right-0 sm:top-0 sm:z-0 sm:mx-0 sm:h-80 sm:w-44 sm:scale-[1.5] sm:translate-x-6 sm:-translate-y-25 md:h-[20rem] md:scale-[1.8] md:translate-x-8 md:-translate-y-35">
                        </div>
                        <div class="mt-4 flex items-center justify-between gap-3 border-t border-white/10 pt-4 text-sm">
                            <span class="font-balinese text-lg text-accent" lang="ban" aria-hidden="true">ᬒᬁ</span>
                            <span class="text-right font-semibold text-foreground/50">Belajar aksara lewat contoh visual</span>
                        </div>
                    </div>
                </div>
            </section>

            <dialog id="learning-picker" data-learning-picker aria-labelledby="learning-picker-title" class="m-auto h-[min(85dvh,48rem)] max-h-[calc(100dvh-2.5rem)] w-[min(44rem,calc(100%-2rem))] flex-col overflow-hidden rounded-card bg-surface p-0 text-foreground shadow-2xl backdrop:bg-black/70 backdrop:backdrop-blur-sm">
                <header class="flex shrink-0 items-center justify-between gap-4 border-b border-white/10 bg-surface px-4 pb-4 pt-4 sm:px-5 sm:pt-5">
                        <div>
                            <p class="mb-1 text-xs font-bold text-accent">Petualangan belajar</p>
                            <h2 id="learning-picker-title" class="font-display text-xl font-bold sm:text-2xl">Mau belajar apa hari ini?</h2>
                            <p class="mt-1.5 text-sm text-foreground/60">Pilih satu topik untuk melihat materi dan mulai berlatih.</p>
                        </div>
                        <button type="button" data-learning-picker-close aria-label="Tutup pilihan materi" class="grid size-9 shrink-0 place-items-center rounded-full border border-white/10 bg-background text-foreground/70 transition hover:border-accent/50 hover:bg-accent/10 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-4" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
                </header>
                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-5">
                    <div class="space-y-4">
                        @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                            @php($groupPaths = $learningPaths->where('group', $group))
                            <section aria-labelledby="learning-picker-{{ $group }}">
                                <header class="mb-2.5 flex items-center gap-2.5">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $group === 'paribasa' ? 'bg-accent/10 text-accent' : 'bg-primary/20 text-accent' }}" aria-hidden="true">
                                    @if ($group === 'paribasa')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><path d="m4 19 6-14 5 14M6 14h7M15 19l3-7 3 7M16 17h4"/></svg>
                                    @endif
                                    </span>
                                    <h3 id="learning-picker-{{ $group }}" class="font-display text-base font-bold">{{ $label }}</h3>
                                    <span class="rounded-full bg-surface px-2 py-0.5 text-[11px] font-bold text-foreground/50">{{ $groupPaths->count() }} materi</span>
                                </header>
                                <nav class="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3" aria-label="Materi {{ $label }}">
                                    @foreach ($groupPaths as $learningPath)
                                        <a href="{{ route('learning.paths.show', $learningPath->slug) }}" class="group flex min-h-36 flex-col rounded-card bg-background p-3 transition duration-150 hover:-translate-y-0.5 hover:bg-background/70 hover:shadow-lg hover:shadow-black/20 focus-visible:outline-2 focus-visible:outline-accent">
                                            <span class="grid size-9 place-items-center rounded-xl {{ $group === 'paribasa' ? 'bg-accent/10 text-accent' : 'bg-primary/20 text-accent' }} transition-colors group-hover:bg-accent group-hover:text-background" aria-hidden="true">
                                                @switch($learningPath->slug)
                                                    @case('cecimpedan')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M9.8 9a2.2 2.2 0 1 1 3.8 1.5c-.9.9-1.6 1.1-1.6 2.5M12 16.5h.01"/></svg>
                                                        @break
                                                    @case('peparikan')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M4 7h10M4 12h16M4 17h10"/><path d="M18 5v8.2a2.3 2.3 0 1 1-2-2.3V7l5-1v5.2a2.3 2.3 0 1 1-2-2.3V5z"/></svg>
                                                        @break
                                                    @case('sesonggakan')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M8 11H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5v6a7 7 0 0 1-7 7M19 11h-3a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5v6a7 7 0 0 1-7 7"/></svg>
                                                        @break
                                                    @case('wreastra-gantungan')
                                                        <span class="font-balinese text-2xl" lang="ban">ᬅ</span>
                                                        @break
                                                    @case('pengangge-suara')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M3 10v4M7 7v10M11 4v16M15 7v10M19 10v4M22 8v8"/></svg>
                                                        @break
                                                    @case('pengangge-tengenan')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M4 12h12M12 6l6 6-6 6"/><circle cx="20" cy="12" r="1" fill="currentColor" stroke="none"/></svg>
                                                        @break
                                                    @case('pengangge-arda-suara')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M8 5 3 12l5 7M16 5l5 7-5 7M13 4l-2 16"/></svg>
                                                        @break
                                                    @case('angka-aksara-bali')
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M8 4 5 20M17 4l-3 16M3 9h18M2 15h18"/></svg>
                                                        @break
                                                @endswitch
                                            </span>
                                            <span class="mt-2.5 block font-display text-sm font-bold leading-5 text-foreground/90 transition-colors group-hover:text-accent">{{ $learningPath->title }}</span>
                                            <span class="mt-1 flex-1 text-xs leading-5 text-foreground/55">{{ $learningPath->description }}</span>
                                            <span class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-bold text-accent">Mulai belajar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 transition-transform group-hover:translate-x-1"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                                        </a>
                                    @endforeach
                                </nav>
                            </section>
                        @endforeach
                    </div>
                </div>
            </dialog>

            <section id="tentang" class="mx-auto max-w-7xl px-6 pb-20 lg:px-10">
                <div class="grid gap-8 rounded-card bg-surface p-7 sm:p-10 lg:grid-cols-[.9fr_1.1fr] lg:items-center lg:p-12">
                    <div>
                        <p class="font-semibold text-accent">Tentang ARTASWAHA</p>
                        <h2 class="mt-3 font-display text-3xl font-bold leading-tight sm:text-4xl">Belajar budaya Bali, dekat dengan keseharian anak.</h2>
                        <p class="mt-5 max-w-xl font-readable leading-7 text-foreground/65">ARTASWAHA adalah platform edukasi bahasa, sastra, dan aksara Bali yang mengajak anak belajar lewat contoh, cerita, dan latihan singkat.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <article class="rounded-3xl bg-background p-5">
                            <span class="grid size-10 place-items-center rounded-full bg-accent/10 text-accent" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg></span>
                            <h3 class="mt-4 font-display font-bold">Paribasa</h3>
                            <p class="mt-2 font-readable text-sm leading-6 text-foreground/60">Cecimpedan, peparikan, dan sesonggakan.</p>
                        </article>
                        <article class="rounded-3xl bg-background p-5">
                            <span class="grid size-10 place-items-center rounded-full bg-primary/20 font-balinese text-xl text-accent" lang="ban" aria-hidden="true">ᬅ</span>
                            <h3 class="mt-4 font-display font-bold">Aksara Bali</h3>
                            <p class="mt-2 font-readable text-sm leading-6 text-foreground/60">Belajar bentuk, tanda, gantungan, dan angka.</p>
                        </article>
                        <article class="rounded-3xl bg-background p-5">
                            <span class="grid size-10 place-items-center rounded-full bg-accent/10 text-accent" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg></span>
                            <h3 class="mt-4 font-display font-bold">Belajar bareng</h3>
                            <p class="mt-2 font-readable text-sm leading-6 text-foreground/60">Buat kuis dan ajak teman masuk room.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section id="keunggulan" class="mx-auto max-w-7xl px-6 pb-20 lg:px-10">
                <div class="mb-8 max-w-2xl">
                    <p class="font-semibold text-accent">Keunggulan ARTASWAHA</p>
                    <h2 class="mt-2 font-display text-3xl font-bold sm:text-4xl">Belajar Bali lewat tantangan kecil yang seru</h2>
                    <p class="mt-4 font-readable leading-7 text-foreground/65">Pilih materi, tuntaskan misi belajarmu, lalu ajak teman ke arena kuis.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <article class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="grid size-12 place-items-center rounded-full bg-accent/10 text-accent" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6"><circle cx="12" cy="12" r="9"/><path d="m16.2 7.8-2.4 6-6 2.4 2.4-6 6-2.4Z"/></svg></span>
                        <h3 class="mt-5 font-display text-xl font-bold">Mulai petualanganmu</h3>
                        <p class="mt-3 font-readable leading-7 text-foreground/65">Pilih materi yang kamu suka, tuntaskan satu tantangan, lalu lanjutkan belajar kapan saja. Progresmu tersimpan di perangkat.</p>
                    </article>
                    <article class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="grid size-12 place-items-center rounded-full bg-primary/20 font-balinese text-2xl text-accent" lang="ban" aria-hidden="true">ᬅ</span>
                        <h3 class="mt-5 font-display text-xl font-bold">Jelajahi paribasa dan aksara</h3>
                        <p class="mt-3 font-readable leading-7 text-foreground/65">Tebak Cecimpedan, kenali Peparikan, dan latih Aksara Bali lewat contoh serta latihan interaktif.</p>
                    </article>
                    <article class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="grid size-12 place-items-center rounded-full bg-primary/20 text-accent" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg></span>
                        <h3 class="mt-5 font-display text-xl font-bold">Ajak teman ke arena kuis</h3>
                        <p class="mt-3 font-readable leading-7 text-foreground/65">Buat kuis atau masuk ke room, jawab bersama, dan lihat peringkat berubah secara langsung.</p>
                    </article>
                </div>
            </section>

            <section id="testimoni" class="mx-auto max-w-7xl px-6 pb-20 lg:px-10">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="font-semibold text-accent">Apa kata mereka</p>
                        <h2 class="mt-2 font-display text-3xl font-bold sm:text-4xl">Belajar yang terasa dekat dan menyenangkan</h2>
                    </div>
                    <p class="max-w-md font-readable text-sm leading-6 text-foreground/55">Kutipan berikut adalah contoh rancangan, bukan testimoni pengguna nyata.</p>
                </div>
                <div data-testimonial-carousel>
                    <div data-testimonial-track role="region" aria-label="Daftar contoh testimoni" class="flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain pb-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden md:grid md:grid-cols-3 md:overflow-visible md:pb-0 md:snap-none">
                    <figure data-testimonial-slide class="flex h-[22rem] w-[88%] shrink-0 snap-start flex-col rounded-card bg-surface p-6 sm:w-[72%] sm:p-7 md:w-auto">
                        <blockquote class="line-clamp-5 flex-1 font-readable text-lg leading-7">Tebak-teki Bali bikin aku ingin tahu jawaban yang lain juga.</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                            <img src="{{ asset('avatar/kupu_kupu.webp') }}" alt="" class="size-12 rounded-full object-cover ring-2 ring-primary/40">
                            <span>
                                <span class="block text-sm font-bold">Murid</span>
                                <span class="mt-0.5 block text-xs text-foreground/50">Ilustrasi · contoh suara</span>
                            </span>
                        </figcaption>
                    </figure>
                    <figure data-testimonial-slide class="flex h-[22rem] w-[88%] shrink-0 snap-start flex-col rounded-card bg-surface p-6 sm:w-[72%] sm:p-7 md:w-auto">
                        <blockquote class="line-clamp-5 flex-1 font-readable text-lg leading-7">Materinya singkat, jadi bisa kami pelajari bersama di rumah.</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                            <img src="{{ asset('avatar/gajah.webp') }}" alt="" class="size-12 rounded-full object-cover ring-2 ring-primary/40">
                            <span>
                                <span class="block text-sm font-bold">Keluarga</span>
                                <span class="mt-0.5 block text-xs text-foreground/50">Ilustrasi · contoh suara</span>
                            </span>
                        </figcaption>
                    </figure>
                    <figure data-testimonial-slide class="flex h-[22rem] w-[88%] shrink-0 snap-start flex-col rounded-card bg-surface p-6 sm:w-[72%] sm:p-7 md:w-auto">
                        <blockquote class="line-clamp-5 flex-1 font-readable text-lg leading-7">Kuis bersama memberi cara baru untuk mengenalkan paribasa.</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                            <img src="{{ asset('avatar/jalak_bali.webp') }}" alt="" class="size-12 rounded-full object-cover ring-2 ring-primary/40">
                            <span>
                                <span class="block text-sm font-bold">Pendidik</span>
                                <span class="mt-0.5 block text-xs text-foreground/50">Ilustrasi · contoh suara</span>
                            </span>
                        </figcaption>
                    </figure>
                    </div>
                    <div data-testimonial-controls class="mt-4 flex items-center justify-between gap-3 md:hidden">
                        <button type="button" data-testimonial-previous aria-label="Testimoni sebelumnya" class="grid size-11 place-items-center rounded-full border border-white/10 bg-surface text-foreground transition hover:border-accent/50 hover:text-accent disabled:cursor-not-allowed disabled:opacity-35 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
                        </button>
                        <p data-testimonial-status class="text-sm font-semibold tabular-nums text-foreground/60" aria-live="polite" aria-atomic="true">1 / 3</p>
                        <button type="button" data-testimonial-next aria-label="Testimoni berikutnya" class="grid size-11 place-items-center rounded-full border border-white/10 bg-surface text-foreground transition hover:border-accent/50 hover:text-accent disabled:cursor-not-allowed disabled:opacity-35 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </section>

            <section id="faq" class="mx-auto max-w-4xl px-6 pb-24 lg:px-10">
                <div class="mb-8 text-center">
                    <p class="font-semibold text-accent">FAQ</p>
                    <h2 class="mt-2 font-display text-3xl font-bold sm:text-4xl">Yang sering ditanyakan</h2>
                </div>
                <div class="space-y-3">
                    <details class="group rounded-card bg-surface p-5 sm:p-6">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-display text-lg font-bold focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                            Apa itu ARTASWAHA?
                            <span class="text-xl text-accent transition-transform group-open:rotate-45" aria-hidden="true">＋</span>
                        </summary>
                        <p class="mt-4 max-w-3xl font-readable leading-7 text-foreground/65">ARTASWAHA adalah platform edukasi bahasa, sastra, dan aksara Bali dengan materi singkat serta latihan interaktif.</p>
                    </details>
                    <details class="group rounded-card bg-surface p-5 sm:p-6">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-display text-lg font-bold focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                            Apa saja yang bisa dipelajari?
                            <span class="text-xl text-accent transition-transform group-open:rotate-45" aria-hidden="true">＋</span>
                        </summary>
                        <p class="mt-4 max-w-3xl font-readable leading-7 text-foreground/65">Paribasa Bali mencakup Cecimpedan, Peparikan, dan Sesonggakan. Aksara Bali mencakup Wreastra beserta gantungannya, Pengangge Suara, Pengangge Tengenan, Pengangge Arda Suara, dan angka.</p>
                    </details>
                    <details class="group rounded-card bg-surface p-5 sm:p-6">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-display text-lg font-bold focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                            Apakah harus membuat akun untuk mulai belajar?
                            <span class="text-xl text-accent transition-transform group-open:rotate-45" aria-hidden="true">＋</span>
                        </summary>
                        <p class="mt-4 max-w-3xl font-readable leading-7 text-foreground/65">Belajar dan bergabung ke kuis bisa memakai profil anonim di perangkat ini. Untuk membuat kuis sendiri, kamu perlu masuk atau mendaftar.</p>
                    </details>
                </div>
            </section>
            </div>
</x-layouts.app>
