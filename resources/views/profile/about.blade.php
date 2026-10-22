<x-layouts.profile :learning-paths="$learningPaths" :user="$user" active="about">
    <article class="rounded-card bg-surface p-5 sm:p-8">
        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-white/10 pb-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold text-accent">Ruang informasi</p>
                <h2 class="mt-2 font-display text-3xl font-bold">Tentang ARTASWAHA</h2>
                <p class="mt-3 leading-7 text-foreground/65">ARTASWAHA - Platform Edukasi Bahasa, Sastra, dan Aksara Bali. Sistem ini menyajikan materi mandiri dan kuis bersama dalam room.</p>
            </div>
            <span class="rounded-full bg-primary/15 px-4 py-2 text-sm font-bold text-accent">Versi {{ config('app.version') }}</span>
        </header>

        <section class="py-6" aria-labelledby="materials-title">
            <h3 id="materials-title" class="font-display text-xl font-bold">Materi pembelajaran</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-3xl bg-background p-5">
                    <h4 class="font-bold text-accent">Paribasa Bali</h4>
                    <p class="mt-2 text-sm leading-6 text-foreground/70">Cecimpedan, Peparikan, dan Sesonggakan.</p>
                </div>
                <div class="rounded-3xl bg-background p-5">
                    <h4 class="font-bold text-accent">Aksara Bali</h4>
                    <p class="mt-2 text-sm leading-6 text-foreground/70">Aksara Wreastra beserta gantungannya, Pengangge Suara, Pengangge Tengenan, Pengangge Arda Suara, dan angka Aksara Bali.</p>
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 pt-6" aria-labelledby="privacy-title">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-accent">Data dan privasi</p>
                    <h3 id="privacy-title" class="mt-1 font-display text-xl font-bold">Ringkasan kebijakan privasi</h3>
                </div>
                <span class="text-xs text-foreground/50">Berlaku untuk fitur yang tersedia saat ini</span>
            </div>

            <dl class="mt-5 space-y-4 text-sm leading-6">
                <div class="rounded-2xl bg-background p-4">
                    <dt class="font-bold">Data yang digunakan</dt>
                    <dd class="mt-1 text-foreground/65">Akun menyimpan nama, alamat email, dan kata sandi dalam bentuk terlindungi. Belajar tanpa akun menggunakan ID acak pada cookie perangkat untuk mengingat progres; progres perangkat dapat ditautkan ke akun saat kamu masuk atau mendaftar. Kuis dan room menyimpan pertanyaan, nama tampilan peserta, jawaban, serta skor yang diperlukan untuk menjalankan sesi.</dd>
                </div>
                <div class="rounded-2xl bg-background p-4">
                    <dt class="font-bold">Penggunaan dan keterlihatan</dt>
                    <dd class="mt-1 text-foreground/65">Data dipakai untuk menyediakan akun, menyimpan progres, dan menjalankan kuis. Nama tampilan serta skor dapat terlihat oleh peserta room ketika peringkat diaktifkan. Kuis yang dibuat hanya ditampilkan kepada pembuatnya; room dibagikan melalui kode undangan.</dd>
                </div>
                <div class="rounded-2xl bg-background p-4">
                    <dt class="font-bold">Kontrol atas data</dt>
                    <dd class="mt-1 text-foreground/65">Jangan gunakan nama lengkap atau informasi sensitif anak sebagai nama tampilan. Saat ini belum ada penghapusan akun mandiri; untuk permintaan akses, koreksi, atau penghapusan data, hubungi pengelola ARTASWAHA melalui kanal resmi.</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs leading-5 text-foreground/45">Ringkasan ini menjelaskan perilaku sistem saat ini. Masa penyimpanan dan kanal kontak pengelola perlu ditetapkan sebelum layanan digunakan secara luas.</p>
        </section>
    </article>
</x-layouts.profile>
