<x-layouts.app :learning-paths="$learningPaths">
            <section class="mx-auto grid min-h-[calc(100svh-6rem)] max-w-7xl items-center gap-12 px-6 pb-16 pt-10 md:grid-cols-[1.05fr_.95fr] md:pb-16 md:pt-16 lg:px-10">
                <div class="max-w-2xl">
                    <h1 class="font-display text-5xl font-bold leading-[1.08] tracking-tight sm:text-6xl lg:text-7xl">Bahasa Bali jadi <span class="text-accent">seru</span> dipelajari.</h1>
                    <p class="mt-6 max-w-xl font-readable text-lg leading-8 text-foreground/75 sm:text-xl">Kenali paribasa dan aksara Bali lewat cerita, latihan singkat, dan permainan yang bisa langsung dicoba. Mulai tanpa akun; progres tersimpan di perangkatmu.</p>
                    <div id="mulai" class="mt-9 flex flex-wrap gap-3">
                        <a href="#jalur" class="inline-flex min-h-14 items-center justify-center rounded-full bg-primary px-8 py-4 font-bold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Mulai belajar gratis</a>
                        <a href="{{ auth()->check() ? route('quizzes.index') : route('login') }}" class="inline-flex min-h-14 items-center justify-center rounded-full border border-white/15 px-7 py-4 font-bold transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Gabung kuis</a>
                    </div>
                    <p class="mt-4 text-sm text-foreground/55">Belajar mandiri tanpa akun. Untuk kuis bersama, masuk atau daftar terlebih dahulu.</p>
                </div>

                <div class="mx-auto w-full max-w-lg py-4 md:pl-8">
                    <div class="relative overflow-hidden rounded-card border border-white/10 bg-surface p-7 sm:p-9">
                        <div class="flex items-center gap-3 text-sm font-semibold text-accent">
                            <span aria-hidden="true">✦</span>
                            <span>Cecimpedan</span>
                        </div>
                        <p class="mt-8 font-display text-2xl font-bold leading-relaxed sm:text-3xl">“Bawak-bawak madaging banyu, apa?”</p>
                        <p class="mt-3 font-readable leading-7 text-foreground/65">Teka-teki: kecil, berisi air. Coba tebak sebelum membuka jawabannya.</p>
                        <details class="group mt-8">
                            <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between rounded-full bg-primary px-6 py-4 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                                Lihat jawaban
                                <span class="text-xl" aria-hidden="true">＋</span>
                            </summary>
                            <p class="mt-5 border-l-2 border-accent pl-4 font-readable text-lg font-semibold text-accent">Kelapa muda</p>
                        </details>
                        <div class="mt-9 flex items-center gap-2 text-xs font-semibold text-foreground/45">
                            <span class="h-px flex-1 bg-white/10"></span>
                            Belajar lewat cerita dan permainan
                            <span class="h-px flex-1 bg-white/10"></span>
                        </div>
                    </div>
                </div>
            </section>

            <section id="jalur" class="mx-auto max-w-7xl px-6 pb-20 lg:px-10">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="font-semibold text-accent">Jalur belajar</p>
                        <h2 class="mt-2 font-display text-3xl font-bold sm:text-4xl">Pilih yang ingin kamu pelajari</h2>
                    </div>
                    <p class="max-w-md font-readable text-foreground/65">Mulai dari pengenalan, lalu lanjutkan ke latihan. Setiap bagian dirancang singkat dan mudah diulang.</p>
                </div>
                <div class="grid gap-5 lg:grid-cols-2">
                    <article class="rounded-card bg-surface p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-accent">Jalur 1</p>
                                <h3 class="mt-2 font-display text-2xl font-bold">Paribasa Bali</h3>
                                <p class="mt-3 max-w-md font-readable leading-7 text-foreground/65">Belajar ungkapan, permainan kata, dan teka-teki yang hidup dalam keseharian Bali.</p>
                            </div>
                            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-accent/10 text-xl text-accent" aria-hidden="true">✦</span>
                        </div>
                        <ul class="mt-7 flex flex-wrap gap-2 text-sm font-semibold">
                            @foreach ($learningPaths->where('group', 'paribasa') as $learningPath)
                                <li><a class="inline-flex rounded-full bg-background px-4 py-2 transition hover:text-accent" href="{{ route('learning.paths.show', $learningPath->slug) }}">{{ $learningPath->title }}</a></li>
                            @endforeach
                        </ul>
                        <a href="{{ route('learning.paths.show', 'cecimpedan') }}" class="mt-8 inline-flex min-h-12 items-center rounded-full bg-primary px-6 py-3 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Jelajahi paribasa</a>
                    </article>
                    <article class="rounded-card bg-surface p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-accent">Jalur 2</p>
                                <h3 class="mt-2 font-display text-2xl font-bold">Aksara Bali</h3>
                                <p class="mt-3 max-w-md font-readable leading-7 text-foreground/65">Kenali bentuk aksara, tanda bunyi, pasangan, dan angka melalui contoh serta latihan menulis.</p>
                            </div>
                            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-primary/15 font-balinese text-2xl text-accent" lang="ban" aria-hidden="true">ᬅ</span>
                        </div>
                        <ul class="mt-7 flex flex-wrap gap-2 text-sm font-semibold">
                            @foreach ($learningPaths->where('group', 'aksara') as $learningPath)
                                <li><a class="inline-flex rounded-full bg-background px-4 py-2 transition hover:text-accent" href="{{ route('learning.paths.show', $learningPath->slug) }}">{{ $learningPath->title }}</a></li>
                            @endforeach
                        </ul>
                        <a href="{{ route('learning.paths.show', 'wreastra-gantungan') }}" class="mt-8 inline-flex min-h-12 items-center rounded-full bg-primary px-6 py-3 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Jelajahi aksara</a>
                    </article>
                </div>
            </section>

            <section id="room" class="mx-6 mb-16 rounded-[2rem] border border-white/10 bg-surface px-6 py-10 sm:px-10 md:mx-auto md:max-w-7xl md:py-14 lg:px-14">
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                    <div>
                        <p class="font-semibold text-accent">Belajar bareng</p>
                        <h2 class="mt-2 font-display text-3xl font-bold">Ada kode room dari teman?</h2>
                        <p class="mt-3 max-w-xl font-readable leading-7 text-foreground/65">Masuk ke kuis bersama, jawab pertanyaan, dan lihat progres sesi secara langsung.</p>
                    </div>
                    <a href="{{ auth()->check() ? route('quizzes.index') : route('login') }}" class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-full bg-accent px-7 py-3 font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Masuk untuk ikut kuis</a>
                </div>
            </section>

            <section id="tentang" class="mx-auto max-w-7xl px-6 pb-20 lg:px-10">
                <div class="grid gap-8 rounded-card bg-surface p-7 sm:p-10 lg:grid-cols-[.9fr_1.1fr] lg:items-center lg:p-12">
                    <div>
                        <p class="font-semibold text-accent">Tentang ARTASWAHA</p>
                        <h2 class="mt-3 font-display text-3xl font-bold leading-tight sm:text-4xl">Belajar budaya Bali, dekat dengan keseharian anak.</h2>
                        <p class="mt-5 max-w-xl font-readable leading-7 text-foreground/65">ARTASWAHA adalah platform edukasi bahasa, sastra, dan aksara Bali yang mengajak anak belajar lewat contoh, cerita, dan latihan singkat.</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <article class="rounded-3xl bg-background p-5">
                            <span class="grid size-10 place-items-center rounded-full bg-accent/10 text-lg text-accent" aria-hidden="true">✦</span>
                            <h3 class="mt-4 font-display font-bold">Paribasa</h3>
                            <p class="mt-2 font-readable text-sm leading-6 text-foreground/60">Cecimpedan, peparikan, dan sesonggakan.</p>
                        </article>
                        <article class="rounded-3xl bg-background p-5">
                            <span class="grid size-10 place-items-center rounded-full bg-primary/20 font-balinese text-xl text-accent" lang="ban" aria-hidden="true">ᬅ</span>
                            <h3 class="mt-4 font-display font-bold">Aksara Bali</h3>
                            <p class="mt-2 font-readable text-sm leading-6 text-foreground/60">Belajar bentuk, tanda, gantungan, dan angka.</p>
                        </article>
                        <article class="rounded-3xl bg-background p-5">
                            <span class="grid size-10 place-items-center rounded-full bg-accent/10 text-lg text-accent" aria-hidden="true">♫</span>
                            <h3 class="mt-4 font-display font-bold">Belajar bareng</h3>
                            <p class="mt-2 font-readable text-sm leading-6 text-foreground/60">Buat kuis dan ajak teman masuk room.</p>
                        </article>
                    </div>
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
                <div class="grid gap-4 md:grid-cols-3">
                    <figure class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="text-3xl text-accent" aria-hidden="true">“</span>
                        <blockquote class="mt-2 font-readable text-lg leading-7">Tebak-teki Bali bikin aku ingin tahu jawaban yang lain juga.</blockquote>
                        <figcaption class="mt-6 text-sm font-semibold text-foreground/50">Contoh suara murid</figcaption>
                    </figure>
                    <figure class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="text-3xl text-accent" aria-hidden="true">“</span>
                        <blockquote class="mt-2 font-readable text-lg leading-7">Materinya singkat, jadi bisa kami pelajari bersama di rumah.</blockquote>
                        <figcaption class="mt-6 text-sm font-semibold text-foreground/50">Contoh suara keluarga</figcaption>
                    </figure>
                    <figure class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="text-3xl text-accent" aria-hidden="true">“</span>
                        <blockquote class="mt-2 font-readable text-lg leading-7">Kuis bersama memberi cara baru untuk mengenalkan paribasa.</blockquote>
                        <figcaption class="mt-6 text-sm font-semibold text-foreground/50">Contoh suara pendidik</figcaption>
                    </figure>
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
                        <p class="mt-4 max-w-3xl font-readable leading-7 text-foreground/65">Belajar mandiri bisa dimulai tanpa akun dan progres tersimpan di perangkat. Untuk membuat atau bergabung ke kuis bersama, kamu perlu masuk.</p>
                    </details>
                </div>
            </section>
</x-layouts.app>
