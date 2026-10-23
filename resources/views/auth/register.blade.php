<x-layouts.auth title="Daftar — ARTASWAHA">
    <p class="inline-flex items-center gap-2 rounded-full bg-primary/15 px-3 py-1.5 text-xs font-bold text-accent">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2L12 3Z"/><path d="m19 14 .9 2.1L22 17l-2.1.9L19 20l-.9-2.1L16 17l2.1-.9L19 14Z"/></svg>
        Petualangan baru dimulai
    </p>
    <h1 class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Yuk, buat akun!</h1>
    <p class="mt-1.5 font-readable leading-6 text-foreground/60">Simpan progres belajarmu dan ajak teman main kuis.</p>
    @if ($errors->any())<p role="alert" class="mt-5 rounded-2xl border border-danger/25 bg-danger/10 p-4 text-sm text-danger">{{ $errors->first() }}</p>@endif
    <form method="POST" action="{{ route('register.store') }}" class="mt-5 space-y-3">
        @csrf
        <label for="register-name" class="block text-sm font-bold">Nama tampilan
            <input id="register-name" name="name" type="text" value="{{ old('name') }}" required maxlength="60" autocomplete="nickname" class="mt-1.5 min-h-11 w-full rounded-2xl border border-white/10 bg-background px-4 text-foreground outline-none transition hover:border-white/20 focus:border-accent focus:ring-4 focus:ring-accent/10">
        </label>
        <label for="register-email" class="block text-sm font-bold">Email
            <input id="register-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1.5 min-h-11 w-full rounded-2xl border border-white/10 bg-background px-4 text-foreground outline-none transition hover:border-white/20 focus:border-accent focus:ring-4 focus:ring-accent/10">
        </label>
        <label for="register-password" class="block text-sm font-bold">Kata sandi
            <span class="mt-1.5 flex min-h-11 items-center rounded-2xl border border-white/10 bg-background transition hover:border-white/20 focus-within:border-accent focus-within:ring-4 focus-within:ring-accent/10">
                <input id="register-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="min-w-0 flex-1 bg-transparent px-4 text-foreground outline-none">
                <button type="button" data-password-toggle aria-controls="register-password" aria-pressed="false" class="mr-2 rounded-xl px-3 py-2 text-xs font-bold text-accent transition hover:bg-primary/15 focus-visible:outline-2 focus-visible:outline-accent">Tampilkan</button>
            </span>
        </label>
        <label for="register-password-confirmation" class="block text-sm font-bold">Ulangi kata sandi
            <span class="mt-1.5 flex min-h-11 items-center rounded-2xl border border-white/10 bg-background transition hover:border-white/20 focus-within:border-accent focus-within:ring-4 focus-within:ring-accent/10">
                <input id="register-password-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="min-w-0 flex-1 bg-transparent px-4 text-foreground outline-none">
                <button type="button" data-password-toggle aria-controls="register-password-confirmation" aria-pressed="false" class="mr-2 rounded-xl px-3 py-2 text-xs font-bold text-accent transition hover:bg-primary/15 focus-visible:outline-2 focus-visible:outline-accent">Tampilkan</button>
            </span>
        </label>
        <button class="group flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 font-extrabold text-white transition hover:brightness-110 active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" type="submit">
            Buat akun gratis
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5 transition-transform group-hover:translate-x-1" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </form>
    <p class="mt-4 text-center text-sm text-foreground/60">Sudah punya akun? <a class="rounded-full font-extrabold text-accent hover:underline focus-visible:outline-2 focus-visible:outline-accent" href="{{ route('login') }}">Masuk</a></p>
</x-layouts.auth>
