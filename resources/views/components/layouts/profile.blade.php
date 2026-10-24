@props(['learningPaths', 'user' => null, 'profile' => null, 'active' => 'progress'])
@php
    $person = $profile ?? $user;
    $isAnonymous = $profile !== null;
@endphp

<x-layouts.app :learning-paths="$learningPaths" :show-footer="false" :title="$active === 'about' ? 'Tentang ARTASWAHA' : 'Profil — ARTASWAHA'">
    <div class="mx-auto grid min-h-[calc(100svh-6rem)] max-w-7xl gap-5 px-6 py-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-center lg:px-10">
        <aside class="rounded-card bg-surface p-5 sm:p-6 lg:sticky lg:top-28 lg:self-start" aria-label="Profil dan navigasi akun">
            <div class="flex items-center gap-4 lg:flex-col lg:items-start">
                <img src="{{ str_starts_with($person->avatar ?? '', 'avatars/') ? route('profile.avatar.show') : asset($person->avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)) }}" alt="Avatar {{ $person->name }}" class="size-16 shrink-0 rounded-full object-cover ring-2 ring-primary/40">
                <div class="min-w-0">
                    <h1 class="break-words font-display text-xl font-bold">{{ $person->name }}</h1>
                    <p class="mt-1 break-all text-sm {{ $isAnonymous ? 'font-semibold text-accent' : 'text-foreground/55' }}">{{ $isAnonymous ? 'Akun anonim' : $person->email }}</p>
                    <button type="button" data-avatar-open class="mt-3 rounded-full bg-primary/15 px-4 py-2 text-sm font-bold text-accent transition hover:bg-primary/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Ganti avatar</button>
                </div>
            </div>

            <nav class="mt-6 grid gap-2 border-t border-white/10 pt-5" aria-label="Menu profil">
                <a href="{{ route('profile.show') }}" @class(['flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-accent', 'bg-primary/15 text-accent' => $active === 'progress', 'text-foreground/75 hover:bg-background hover:text-accent' => $active !== 'progress'])>
                    <span aria-hidden="true">✦</span> Progres belajar
                </a>
                <a href="{{ route('profile.about') }}" @class(['flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-accent', 'bg-primary/15 text-accent' => $active === 'about', 'text-foreground/75 hover:bg-background hover:text-accent' => $active !== 'about'])>
                    <span aria-hidden="true">ⓘ</span> Tentang aplikasi ini
                </a>
                <a href="{{ route($isAnonymous ? 'login' : 'quizzes.index') }}" class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-foreground/75 transition hover:bg-background hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">
                    <span aria-hidden="true">♫</span> Kuis saya @if ($isAnonymous)<span class="ml-auto text-xs text-foreground/45">Perlu akun</span>@endif
                </a>
                @if ($isAnonymous)
                    <a href="{{ route('login') }}" class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-foreground/75 transition hover:bg-background hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><span aria-hidden="true">↗</span> Masuk</a>
                @endif
            </nav>

            <dialog data-avatar-dialog @if ($errors->any()) data-open-on-load @endif class="m-auto max-h-[calc(100dvh-2rem)] w-[min(38rem,calc(100%-2rem))] overflow-hidden rounded-card border border-white/10 bg-surface p-0 text-foreground shadow-2xl backdrop:bg-black/75">
                <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="avatar-modal-content max-h-[calc(100dvh-2rem)] overflow-y-auto overscroll-contain p-5 sm:p-7">
                    @csrf
                    @method('PUT')
                    <header class="sticky top-0 z-10 -mx-5 flex items-center justify-between gap-4 bg-surface px-5 pb-3 pt-1 sm:-mx-7 sm:px-7">
                        <div>
                            <h2 class="font-display text-2xl font-bold">Pilih avatar</h2>
                            <p class="mt-1 text-sm text-foreground/60">Pilih ilustrasi Bali atau unggah gambar milikmu.</p>
                        </div>
                        <button type="button" data-avatar-close aria-label="Tutup" class="grid size-10 shrink-0 place-items-center rounded-full bg-background text-foreground/70 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-4" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
                    </header>

                    @if ($errors->any())
                        <p role="alert" class="mt-4 rounded-2xl bg-danger/10 p-3 text-sm text-danger">{{ $errors->first('avatar_upload') ?: $errors->first('avatar') }}</p>
                    @endif
                    @php
                        $selectedAvatar = old('avatar', $person->avatar && ! str_starts_with($person->avatar, 'avatars/') ? $person->avatar : ($person->avatar ? '' : array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)));
                    @endphp
                    <fieldset class="mt-5">
                        <legend class="mb-3 text-sm font-bold">Avatar bawaan</legend>
                        <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                            @foreach (\App\Services\ProfileAvatarService::DEFAULT_AVATARS as $avatarPath => $avatarName)
                                <label class="group cursor-pointer rounded-2xl p-2 text-center transition hover:bg-background has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-accent">
                                    <input class="peer sr-only" type="radio" name="avatar" value="{{ $avatarPath }}" @checked($selectedAvatar === $avatarPath)>
                                    <span class="mx-auto block size-16 overflow-hidden rounded-full ring-2 ring-transparent transition peer-checked:ring-accent group-hover:scale-105">
                                        <img src="{{ asset($avatarPath) }}" alt="" class="size-full object-cover">
                                    </span>
                                    <span class="mt-2 block truncate text-xs font-semibold text-foreground/70 peer-checked:text-accent">{{ $avatarName }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <label class="mt-5 block rounded-2xl bg-background p-4 text-sm font-semibold">
                        Unggah avatar sendiri
                        <input type="file" name="avatar_upload" accept="image/png,image/jpeg,image/webp" data-avatar-file class="mt-2 block w-full text-sm text-foreground/70 file:mr-3 file:rounded-full file:border-0 file:bg-primary/20 file:px-4 file:py-2 file:font-bold file:text-accent focus-visible:outline-2 focus-visible:outline-accent">
                        <span class="mt-2 block text-xs font-normal text-foreground/50">PNG, JPG, atau WebP · maksimal 2 MB. Jika memilih file, file itu yang digunakan.</span>
                    </label>

                    <section data-avatar-crop hidden class="mt-4 rounded-2xl bg-background p-4" aria-label="Atur potongan avatar">
                        <div class="flex flex-col items-center gap-4 sm:flex-row">
                            <div data-avatar-crop-viewport class="relative size-40 shrink-0 touch-none overflow-hidden rounded-full bg-surface ring-2 ring-accent/70 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" role="group" aria-label="Pratinjau crop avatar; gunakan tombol panah untuk menggeser" tabindex="0"></div>
                            <div class="w-full">
                                <p class="text-sm font-bold">Atur tampilan avatar</p>
                                <p class="mt-1 text-xs text-foreground/55">Geser gambar di dalam lingkaran atau gunakan tombol panah, lalu atur perbesarannya.</p>
                                <label class="mt-4 block text-xs font-semibold text-foreground/70" for="avatar-zoom">Perbesaran</label>
                                <input id="avatar-zoom" data-avatar-zoom type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full accent-accent focus-visible:outline-2 focus-visible:outline-accent" aria-label="Atur perbesaran avatar">
                            </div>
                        </div>
                        <p data-avatar-crop-status role="status" class="mt-3 text-xs text-foreground/55">Bagian di dalam lingkaran yang akan disimpan.</p>
                    </section>

                    <button type="submit" class="mt-6 min-h-12 w-full rounded-full bg-accent px-6 py-3 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Simpan avatar</button>
                </form>
            </dialog>
        </aside>

        <div class="min-w-0">
            @if (session('status'))
                <x-flash-notice class="mb-4">{{ session('status') }}</x-flash-notice>
            @endif
            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
