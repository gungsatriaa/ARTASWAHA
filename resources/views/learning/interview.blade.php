<x-layouts.app :learning-paths="$learningPaths" :show-footer="false" title="Mulai belajar — ARTASWAHA">
    <section class="mx-auto flex min-h-[calc(100svh-6rem)] max-w-5xl flex-col justify-center px-5 py-8 sm:py-12">
        <div class="mb-7 flex items-center justify-between gap-4">
            <a href="{{ $step === 1 ? route('home') : route('learning.interview.show', ['step' => $step - 1]) }}" class="inline-flex items-center gap-2 rounded-full text-sm font-bold text-foreground/60 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><span aria-hidden="true">←</span> {{ $step === 1 ? 'Beranda' : 'Kembali' }}</a>
            <span class="rounded-full border border-white/10 bg-surface px-4 py-2 text-sm font-bold text-accent">{{ $step }} / 4</span>
        </div>

        <div class="mb-6 flex gap-2" aria-label="Langkah {{ $step }} dari 4">
            @for ($index = 1; $index <= 4; $index++)
                <span @class(['h-1.5 flex-1 rounded-full', 'bg-accent' => $index <= $step, 'bg-white/10' => $index > $step])></span>
            @endfor
        </div>

        <div class="grid overflow-hidden rounded-card border border-white/10 bg-surface lg:grid-cols-[minmax(0,1fr)_17rem]">
            <div class="p-6 sm:p-10">
                <p class="font-bold text-accent">Kenalan dulu, yuk</p>
                @if ($step === 1)
                    <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">Budaya Bali apa yang ingin kamu pelajari?</h1>
                    <p class="mt-3 text-foreground/60">Pilih satu untuk memulai. Kamu bisa menjelajahi materi lain nanti.</p>
                    <form method="POST" action="{{ route('learning.interview.store') }}" class="mt-8 space-y-5">
                        @csrf
                        <input type="hidden" name="step" value="1">
                        @foreach (['paribasa' => 'Bahasa & paribasa', 'aksara' => 'Aksara Bali'] as $group => $label)
                            @if ($learningPaths->where('group', $group)->isNotEmpty())
                                <fieldset>
                                    <legend class="mb-3 font-display text-lg font-bold">{{ $label }}</legend>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        @foreach ($learningPaths->where('group', $group) as $path)
                                            <label class="group cursor-pointer rounded-2xl border border-white/10 bg-background p-4 transition hover:border-accent/50 has-[:checked]:border-accent has-[:checked]:bg-accent/10 focus-within:outline-2 focus-within:outline-accent">
                                                <input type="radio" name="learning_path_id" value="{{ $path->id }}" required @checked((string) old('learning_path_id', $answers['learning_path_id'] ?? '') === (string) $path->id) class="accent-[var(--color-accent)]">
                                                <span class="ml-2 font-bold text-foreground">{{ $path->title }}</span>
                                                <span class="mt-2 block text-sm leading-6 text-foreground/55">{{ $path->description }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif
                        @endforeach
                        @if ($learningPaths->isEmpty())
                            <p class="rounded-2xl border border-white/10 bg-background p-4 text-sm text-foreground/60">Materi belajar belum tersedia. Silakan kembali nanti.</p>
                        @else
                            <button type="submit" class="min-h-12 w-full rounded-full bg-accent px-6 font-extrabold text-background transition hover:brightness-110 sm:w-auto">Lanjutkan <span aria-hidden="true">→</span></button>
                        @endif
                    </form>
                @elseif ($step === 2)
                    <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">Siapakah kamu?</h1>
                    <p class="mt-3 text-foreground/60">Kami akan menyesuaikan cara menyapa dan rekomendasi belajar untukmu.</p>
                    <form method="POST" action="{{ route('learning.interview.store') }}" class="mt-8">
                        @csrf
                        <input type="hidden" name="step" value="2">
                        <fieldset class="grid gap-3 sm:grid-cols-2">
                            <legend class="sr-only">Pilih peran</legend>
                            @foreach ($roles as $value => $label)
                                <label class="flex min-h-20 cursor-pointer items-center gap-4 rounded-2xl border border-white/10 bg-background p-5 text-lg font-bold transition hover:border-accent/50 has-[:checked]:border-accent has-[:checked]:bg-accent/10 focus-within:outline-2 focus-within:outline-accent">
                                    <input type="radio" name="role" value="{{ $value }}" required @checked(old('role', $answers['role'] ?? '') === $value) class="size-5 accent-[var(--color-accent)]">{{ $label }}
                                </label>
                            @endforeach
                        </fieldset>
                        <button type="submit" class="mt-7 min-h-12 w-full rounded-full bg-accent px-6 font-extrabold text-background transition hover:brightness-110 sm:w-auto">Lanjutkan <span aria-hidden="true">→</span></button>
                    </form>
                @elseif ($step === 3)
                    <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">Bagaimana kamu ingin lanjut?</h1>
                    <p class="mt-3 text-foreground/60">{{ auth()->check() ? 'Kamu siap memulai dengan akunmu.' : 'Kamu bebas belajar tanpa akun. Akun memudahkan progres di perangkat lain.' }}</p>
                    <div @class(['mt-8 grid gap-4', 'sm:grid-cols-2' => auth()->guest()])>
                        @guest
                        <form method="POST" action="{{ route('learning.interview.store') }}" class="flex flex-col rounded-2xl border border-white/10 bg-background p-6">
                            @csrf
                            <input type="hidden" name="step" value="3"><input type="hidden" name="mode" value="anonymous">
                            <span class="text-2xl" aria-hidden="true">✦</span>
                            <h2 class="mt-4 font-display text-xl font-bold">Lanjut anonim</h2>
                            <p class="mt-2 flex-1 text-sm leading-6 text-foreground/60">Cukup isi nama dan pilih avatar. Profil belajar tersimpan di perangkat ini.</p>
                            <button type="submit" class="mt-6 min-h-11 rounded-full bg-accent px-5 font-extrabold text-background transition hover:brightness-110">Pilih anonim</button>
                        </form>
                        @endguest
                        <form method="POST" action="{{ route('learning.interview.store') }}" class="flex flex-col rounded-2xl border border-white/10 bg-background p-6">
                            @csrf
                            <input type="hidden" name="step" value="3"><input type="hidden" name="mode" value="account">
                            <span class="text-2xl" aria-hidden="true">✧</span>
                            <h2 class="mt-4 font-display text-xl font-bold">{{ auth()->check() ? 'Lanjut dengan akun' : 'Masuk atau daftar' }}</h2>
                            <p class="mt-2 flex-1 text-sm leading-6 text-foreground/60">{{ auth()->check() ? 'Pilihan belajar ini akan tersimpan bersama akunmu.' : 'Masuk dengan akunmu atau daftar sebagai pengguna baru. Pilihan belajar ini tetap tersimpan.' }}</p>
                            <button type="submit" class="mt-6 min-h-11 rounded-full bg-primary px-5 font-extrabold text-white transition hover:brightness-110">{{ auth()->check() ? 'Mulai belajar' : 'Masuk / daftar' }}</button>
                        </form>
                    </div>
                @else
                    <h1 class="mt-2 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">Apa nama panggilanmu?</h1>
                    <p class="mt-3 text-foreground/60">Nama dan avatar hanya dipakai untuk profil belajar di perangkat ini.</p>
                    <form method="POST" action="{{ route('learning.interview.store') }}" enctype="multipart/form-data" class="mt-8 space-y-6">
                        @csrf
                        <input type="hidden" name="step" value="4">
                        <label class="block text-sm font-bold" for="learner-name">Nama panggilan</label>
                        <input id="learner-name" name="name" value="{{ old('name') }}" required minlength="2" maxlength="80" autocomplete="nickname" placeholder="Misalnya, Komang" class="-mt-4 min-h-12 w-full rounded-2xl border border-white/15 bg-background px-4 text-foreground outline-none focus:border-accent">
                        <div class="rounded-2xl border border-white/10 bg-background p-5">
                            <div class="flex items-center gap-4">
                                <img src="{{ asset(array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)) }}" alt="Contoh avatar acak" class="size-14 rounded-full object-cover ring-2 ring-accent/50">
                                <div><p class="font-bold">Avatar acak</p><p class="text-sm text-foreground/55">Kami memilih satu karakter untukmu secara acak.</p></div>
                            </div>
                            <label class="mt-5 block text-sm font-bold" for="avatar-upload">Atau unggah avatarmu sendiri (opsional)</label>
                            <input id="avatar-upload" name="avatar_upload" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full text-sm text-foreground/65 file:mr-4 file:cursor-pointer file:rounded-full file:border-0 file:bg-primary file:px-4 file:py-2 file:font-bold file:text-white">
                            <p class="mt-2 text-xs text-foreground/45">JPG, PNG, atau WEBP. Maksimal 2 MB dan 2048 × 2048 piksel.</p>
                        </div>
                        <button type="submit" class="min-h-12 w-full rounded-full bg-accent px-6 font-extrabold text-background transition hover:brightness-110 sm:w-auto">Mulai belajar <span aria-hidden="true">→</span></button>
                    </form>
                @endif
                @if ($errors->any())
                    <p role="alert" class="mt-5 rounded-2xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger">{{ $errors->first() }}</p>
                @endif
            </div>
            <aside class="flex flex-col justify-between border-t border-white/10 bg-primary/10 p-6 lg:border-l lg:border-t-0 lg:p-8">
                <div><span class="font-balinese text-4xl text-accent" lang="ban" aria-hidden="true">ᬒᬁ</span><h2 class="mt-4 font-display text-xl font-bold">Petualanganmu dimulai di sini.</h2><p class="mt-3 text-sm leading-7 text-foreground/60">Beberapa langkah singkat akan membantu kami mengenali minatmu. Kamu bisa langsung belajar setelah selesai.</p></div>
                <p class="mt-8 border-t border-white/10 pt-5 text-xs leading-6 text-foreground/45">Tanpa email untuk mode anonim. Menghapus cookie atau data situs dapat membuat profil perangkat ini tidak dapat dipulihkan.</p>
            </aside>
        </div>
    </section>
</x-layouts.app>
