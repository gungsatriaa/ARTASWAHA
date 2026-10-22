<x-layouts.app :learning-paths="$learningPaths">
            <section class="mx-auto grid min-h-[calc(100svh-6rem)] max-w-7xl items-center gap-10 overflow-x-clip px-5 pb-12 pt-8 md:gap-12 md:px-6 md:pb-16 md:pt-16 lg:grid-cols-[1.05fr_.95fr] lg:px-10">
                <div class="max-w-2xl">
                    <h1 class="font-display text-5xl font-bold leading-[1.08] tracking-tight sm:text-6xl lg:text-7xl">Bahasa Bali jadi <span class="text-accent">seru</span> dipelajari.</h1>
                    <p class="mt-6 max-w-xl font-readable text-lg leading-8 text-foreground/75 sm:text-xl">Kenali paribasa dan aksara Bali lewat cerita, latihan singkat, dan permainan yang bisa langsung dicoba. Mulai tanpa akun; progres tersimpan di perangkatmu.</p>
                    <div id="mulai" class="mt-9 flex flex-wrap gap-3">
                        <a href="{{ route('learning.paths.show', 'cecimpedan') }}" class="inline-flex min-h-14 items-center justify-center rounded-full bg-primary px-8 py-4 font-bold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Mulai belajar gratis</a>
                        <a href="{{ auth()->check() ? route('quizzes.index') : route('login') }}" class="inline-flex min-h-14 items-center justify-center rounded-full border border-white/15 px-7 py-4 font-bold transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Gabung kuis</a>
                    </div>
                    <p class="mt-4 text-sm text-foreground/55">Belajar mandiri tanpa akun. Untuk kuis bersama, masuk atau daftar terlebih dahulu.</p>
                </div>

                <div class="mx-auto w-full max-w-lg py-4 lg:pl-8">
                    <div class="relative rounded-card border border-white/10 bg-surface p-7 sm:p-9">
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

            <section id="keunggulan" class="mx-auto max-w-7xl px-6 pb-20 lg:px-10">
                <div class="mb-8 max-w-2xl">
                    <p class="font-semibold text-accent">Keunggulan ARTASWAHA</p>
                    <h2 class="mt-2 font-display text-3xl font-bold sm:text-4xl">Belajar Bali lewat tantangan kecil yang seru</h2>
                    <p class="mt-4 font-readable leading-7 text-foreground/65">Pilih materi, tuntaskan misi belajarmu, lalu ajak teman ke arena kuis.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <article class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="grid size-12 place-items-center rounded-full bg-accent/10 text-xl text-accent" aria-hidden="true">✦</span>
                        <h3 class="mt-5 font-display text-xl font-bold">Mulai petualanganmu</h3>
                        <p class="mt-3 font-readable leading-7 text-foreground/65">Pilih materi yang kamu suka, tuntaskan satu tantangan, lalu lanjutkan belajar kapan saja. Progresmu tersimpan di perangkat.</p>
                    </article>
                    <article class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="grid size-12 place-items-center rounded-full bg-primary/20 font-balinese text-2xl text-accent" lang="ban" aria-hidden="true">ᬅ</span>
                        <h3 class="mt-5 font-display text-xl font-bold">Jelajahi paribasa dan aksara</h3>
                        <p class="mt-3 font-readable leading-7 text-foreground/65">Tebak Cecimpedan, kenali Peparikan, dan latih Aksara Bali lewat contoh serta latihan interaktif.</p>
                    </article>
                    <article class="rounded-card bg-surface p-6 sm:p-7">
                        <span class="grid size-12 place-items-center rounded-full bg-primary/20 text-xl text-accent" aria-hidden="true">♫</span>
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
                <div class="grid gap-4 md:grid-cols-3">
                    <figure class="flex h-[22rem] flex-col rounded-card bg-surface p-6 sm:p-7">
                        <blockquote class="line-clamp-5 flex-1 font-readable text-lg leading-7">Tebak-teki Bali bikin aku ingin tahu jawaban yang lain juga.</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                            <img src="{{ asset('avatar/kupu_kupu.webp') }}" alt="" class="size-12 rounded-full object-cover ring-2 ring-primary/40">
                            <span>
                                <span class="block text-sm font-bold">Murid</span>
                                <span class="mt-0.5 block text-xs text-foreground/50">Ilustrasi · contoh suara</span>
                            </span>
                        </figcaption>
                    </figure>
                    <figure class="flex h-[22rem] flex-col rounded-card bg-surface p-6 sm:p-7">
                        <blockquote class="line-clamp-5 flex-1 font-readable text-lg leading-7">Materinya singkat, jadi bisa kami pelajari bersama di rumah.</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                            <img src="{{ asset('avatar/gajah.webp') }}" alt="" class="size-12 rounded-full object-cover ring-2 ring-primary/40">
                            <span>
                                <span class="block text-sm font-bold">Keluarga</span>
                                <span class="mt-0.5 block text-xs text-foreground/50">Ilustrasi · contoh suara</span>
                            </span>
                        </figcaption>
                    </figure>
                    <figure class="flex h-[22rem] flex-col rounded-card bg-surface p-6 sm:p-7">
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
