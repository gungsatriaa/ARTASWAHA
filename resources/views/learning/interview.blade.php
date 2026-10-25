<x-layouts.app :learning-paths="$learningPaths" :show-footer="false" title="Mulai belajar — ARTASWAHA">
    <section @class(['mx-auto flex min-h-[calc(100svh-6rem)] flex-col justify-start px-4 py-5 sm:justify-center sm:px-5 sm:py-4', 'max-w-6xl' => $step <= 3, 'max-w-4xl' => $step === 4])>
        <div class="mb-4 flex items-center justify-between gap-4">
            <a href="{{ $step === 1 ? route('home') : route('learning.interview.show', ['step' => $step - 1]) }}" class="inline-flex items-center gap-2 rounded-full text-sm font-bold text-foreground/60 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><span aria-hidden="true">←</span> {{ $step === 1 ? 'Beranda' : 'Kembali' }}</a>
            <span class="rounded-full border border-white/10 bg-surface px-4 py-2 text-sm font-bold text-accent">{{ $step }} / 4</span>
        </div>

        <div class="mb-4 flex gap-2" aria-label="Langkah {{ $step }} dari 4">
            @for ($index = 1; $index <= 4; $index++)
                <span @class(['h-1.5 flex-1 rounded-full', 'bg-accent' => $index <= $step, 'bg-white/10' => $index > $step])></span>
            @endfor
        </div>

        <div class="rounded-card border border-white/10 bg-surface">
            <div class="p-4 sm:p-6">
                @if ($errors->any())
                    <p role="alert" class="mb-4 rounded-2xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger">{{ $errors->first() }}</p>
                @endif
                <p class="font-bold text-accent">Kenalan dulu, yuk</p>
                @if ($step === 1)
                    <h1 class="mt-1 font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Budaya Bali apa yang ingin kamu pelajari?</h1>
                    <p class="mt-2 text-sm text-foreground/60">Pilih satu atau beberapa materi yang ingin kamu pelajari.</p>
                    <form method="POST" action="{{ route('learning.interview.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="step" value="1">
                        @foreach (['paribasa' => 'Bahasa & paribasa', 'aksara' => 'Aksara Bali'] as $group => $label)
                            @if ($learningPaths->where('group', $group)->isNotEmpty())
                                <fieldset>
                                    <legend class="mb-2 font-display text-base font-bold">{{ $label }}</legend>
                                    <div @class(['grid gap-2 min-[360px]:grid-cols-2', 'lg:grid-cols-3' => $group === 'paribasa', 'lg:grid-cols-5' => $group === 'aksara'])>
                                        @foreach ($learningPaths->where('group', $group) as $path)
                                            <label class="group cursor-pointer rounded-2xl border border-white/10 bg-background p-3 transition hover:border-accent/50 has-[:checked]:border-accent has-[:checked]:bg-accent/10 focus-within:outline-2 focus-within:outline-accent min-[360px]:last:col-span-2 sm:last:col-span-1">
                                                <input type="checkbox" name="learning_path_ids[]" value="{{ $path->id }}" @checked(in_array((string) $path->id, array_map('strval', old('learning_path_ids', $answers['learning_path_ids'] ?? [])), true)) class="accent-[var(--color-accent)]">
                                                <span class="ml-2 font-bold text-foreground">{{ $path->title }}</span>
                                                <span class="mt-1 block text-xs leading-5 text-foreground/60">{{ $path->description }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif
                        @endforeach
                        @if ($learningPaths->isEmpty())
                            <p class="rounded-2xl border border-white/10 bg-background p-4 text-sm text-foreground/60">Materi belajar belum tersedia. Silakan kembali nanti.</p>
                        @else
                            <div class="flex justify-end border-t border-white/10 pt-4"><button type="submit" class="min-h-12 w-full rounded-full bg-accent px-6 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:w-auto">Lanjutkan <span aria-hidden="true">→</span></button></div>
                        @endif
                    </form>
                @elseif ($step === 2)
                    <h1 class="mt-1 font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Siapakah kamu?</h1>
                    <p class="mt-2 text-sm text-foreground/60">Kami akan menyesuaikan cara menyapa dan rekomendasi belajar untukmu.</p>
                    <form method="POST" action="{{ route('learning.interview.store') }}" class="mt-6">
                        @csrf
                        <input type="hidden" name="step" value="2">
                        <fieldset class="grid gap-2 min-[360px]:grid-cols-2 lg:grid-cols-4">
                            <legend class="sr-only">Pilih peran</legend>
                            @foreach ($roles as $value => $label)
                                <label class="flex min-h-16 cursor-pointer items-center gap-2 rounded-2xl border border-white/10 bg-background p-3 text-sm font-bold transition hover:border-accent/50 has-[:checked]:border-accent has-[:checked]:bg-accent/10 focus-within:outline-2 focus-within:outline-accent sm:min-h-20 sm:gap-3 sm:p-4 sm:text-base">
                                    <input type="radio" name="role" value="{{ $value }}" required @checked(old('role', $answers['role'] ?? '') === $value) class="size-5 shrink-0 accent-[var(--color-accent)]">{{ $label }}
                                </label>
                            @endforeach
                        </fieldset>
                        <div class="mt-6 flex justify-end border-t border-white/10 pt-4"><button type="submit" class="min-h-12 w-full rounded-full bg-accent px-6 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:w-auto">Lanjutkan <span aria-hidden="true">→</span></button></div>
                    </form>
                @elseif ($step === 3)
                    <h1 class="mt-1 font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Bagaimana kamu ingin lanjut?</h1>
                    <p class="mt-2 text-sm text-foreground/60">{{ auth()->check() ? 'Kamu siap memulai dengan akunmu.' : 'Kamu bebas belajar tanpa akun. Akun memudahkan progres di perangkat lain.' }}</p>
                    <div @class(['mt-6 grid gap-3', 'sm:grid-cols-2' => auth()->guest()])>
                        @guest
                        <form method="POST" action="{{ route('learning.interview.store') }}" class="flex flex-col rounded-2xl border border-white/10 bg-background p-4 sm:p-6">
                            @csrf
                            <input type="hidden" name="step" value="3"><input type="hidden" name="mode" value="anonymous">
                            <h2 class="font-display text-xl font-bold">Lanjut anonim</h2>
                            <p class="mt-2 flex-1 text-sm leading-6 text-foreground/60">Isi nama dan avatar berikutnya. Profil hanya tersimpan di perangkat ini dan bisa hilang jika cookie dihapus.</p>
                            <button type="submit" class="mt-5 min-h-12 rounded-full bg-accent px-5 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Pilih anonim</button>
                        </form>
                        @endguest
                        <form method="POST" action="{{ route('learning.interview.store') }}" class="flex flex-col rounded-2xl border border-white/10 bg-background p-4 sm:p-6">
                            @csrf
                            <input type="hidden" name="step" value="3"><input type="hidden" name="mode" value="account">
                            <h2 class="font-display text-xl font-bold">{{ auth()->check() ? 'Lanjut dengan akun' : 'Masuk atau daftar' }}</h2>
                            <p class="mt-2 flex-1 text-sm leading-6 text-foreground/60">{{ auth()->check() ? 'Pilihan belajar ini akan tersimpan bersama akunmu.' : 'Masuk atau daftar agar pilihan dan progres belajarmu tersedia di perangkat lain.' }}</p>
                            <button type="submit" class="mt-5 min-h-12 rounded-full bg-primary px-5 font-extrabold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">{{ auth()->check() ? 'Mulai belajar' : 'Masuk / daftar' }}</button>
                        </form>
                    </div>
                @else
                    <h1 class="mt-1 font-display text-2xl font-extrabold tracking-tight sm:text-3xl">Apa nama panggilanmu?</h1>
                    <p class="mt-2 text-sm text-foreground/60">Lengkapi profil anonim untuk mulai belajar.</p>
                    <form method="POST" action="{{ route('learning.interview.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 lg:grid-cols-2">
                        @csrf
                        <input type="hidden" name="step" value="4">
                        <div class="min-w-0 rounded-2xl border border-white/10 bg-background p-4 sm:p-5">
                            <label class="block text-sm font-bold" for="learner-name">Nama panggilan</label>
                            <p class="mt-1 text-xs leading-5 text-foreground/55">Nama ini akan muncul di profil belajarmu.</p>
                            <input id="learner-name" name="name" value="{{ old('name') }}" required minlength="2" maxlength="80" autocomplete="nickname" placeholder="Misalnya, Komang" class="mt-4 min-h-12 w-full rounded-2xl border border-white/15 bg-surface px-4 text-foreground outline-none focus:border-accent">
                        </div>
                        <div class="min-w-0 rounded-2xl border border-white/10 bg-background p-4 sm:p-5">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset(array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)) }}" alt="Contoh avatar acak" class="size-12 shrink-0 rounded-full object-cover ring-2 ring-accent/50">
                                <div class="min-w-0"><p class="font-bold">Avatar acak</p><p class="text-xs leading-5 text-foreground/55">Pakai karakter acak, atau unggah fotomu.</p></div>
                            </div>
                            <label class="mt-4 block text-sm font-bold" for="avatar-upload">Unggah avatar sendiri (opsional)</label>
                            <input id="avatar-upload" name="avatar_upload" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block max-w-full text-sm text-foreground/65 file:mr-3 file:cursor-pointer file:rounded-full file:border-0 file:bg-primary/20 file:px-4 file:py-2 file:font-bold file:text-accent focus-visible:outline-2 focus-visible:outline-accent">
                            <p class="mt-2 text-xs leading-5 text-foreground/55">JPG, PNG, atau WEBP · maksimal 2 MB dan 2048 × 2048 piksel.</p>
                        </div>
                        <div class="flex justify-end border-t border-white/10 pt-4 lg:col-span-2"><button type="submit" class="min-h-12 w-full rounded-full bg-accent px-6 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:w-auto">Mulai belajar <span aria-hidden="true">→</span></button></div>
                    </form>
                @endif
            </div>
        </div>
        @if ($step === 2 || $step === 3 || $step === 4)
            <p class="mt-4 text-center text-xs leading-5 text-foreground/55">{{ $step === 4 ? 'Profil anonim tersimpan di perangkat ini dan bisa hilang jika cookie dihapus.' : 'Pilihanmu dipakai untuk menyesuaikan pengalaman belajar.' }} <a href="{{ route('privacy-policy') }}" class="font-bold text-accent underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-accent">Lihat penggunaan datamu</a>.</p>
        @endif
    </section>
</x-layouts.app>
