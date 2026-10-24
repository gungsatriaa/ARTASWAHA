<x-layouts.auth title="Masuk — ARTASWAHA">
    <p class="inline-flex items-center gap-2 rounded-full bg-primary/15 px-3 py-1.5 text-xs font-bold text-accent">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M12 3v3M5.64 5.64l2.12 2.12M3 12h3M5.64 18.36l2.12-2.12M12 18v3M18.36 18.36l-2.12-2.12M18 12h3M18.36 5.64l-2.12 2.12"/><circle cx="12" cy="12" r="4"/></svg>
        Siap untuk lanjut belajar?
    </p>
    <h1 class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Senang bertemu lagi!</h1>
    <p class="mt-1.5 font-readable leading-6 text-foreground/60">Masuk dan lanjutkan petualanganmu mengenal bahasa Bali.</p>
    @if (session('status'))<x-flash-notice class="mt-5">{{ session('status') }}</x-flash-notice>@endif
    @if ($errors->any())<p role="alert" class="mt-5 rounded-2xl border border-danger/25 bg-danger/10 p-4 text-sm text-danger">{{ $errors->first() }}</p>@endif
    <form method="POST" action="{{ route('login.store') }}" class="mt-5 space-y-4">
        @csrf
        <label for="login-email" class="block text-sm font-bold">Email
            <input id="login-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-1.5 min-h-11 w-full rounded-2xl border border-white/10 bg-background px-4 text-foreground outline-none transition placeholder:text-foreground/35 hover:border-white/20 focus:border-accent focus:ring-4 focus:ring-accent/10">
        </label>
        <label for="login-password" class="block text-sm font-bold">Kata sandi
            <span class="mt-1.5 flex min-h-11 items-center rounded-2xl border border-white/10 bg-background transition focus-within:border-accent focus-within:ring-4 focus-within:ring-accent/10 hover:border-white/20">
                <input id="login-password" name="password" type="password" required autocomplete="current-password" class="min-w-0 flex-1 bg-transparent px-4 text-foreground outline-none">
                <button type="button" data-password-toggle aria-controls="login-password" aria-pressed="false" class="mr-2 rounded-xl px-3 py-2 text-xs font-bold text-accent transition hover:bg-primary/15 focus-visible:outline-2 focus-visible:outline-accent">Tampilkan</button>
            </span>
        </label>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="inline-flex min-h-10 items-center gap-3 text-sm font-medium text-foreground/70"><input name="remember" type="checkbox" value="1" class="size-4 rounded accent-[var(--color-accent)]"> Ingat saya</label>
            <a href="{{ route('password.request') }}" class="rounded-full text-sm font-bold text-accent hover:underline focus-visible:outline-2 focus-visible:outline-accent">Lupa kata sandi?</a>
        </div>
        <button class="group flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 font-extrabold text-white transition hover:brightness-110 active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" type="submit">
            Masuk dan lanjut belajar
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5 transition-transform group-hover:translate-x-1" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </form>
    <div class="my-5 flex items-center gap-3 text-xs font-semibold text-foreground/40"><span class="h-px flex-1 bg-white/10"></span><span>atau masuk dengan</span><span class="h-px flex-1 bg-white/10"></span></div>
    <a href="{{ route('auth.google.redirect') }}" class="flex min-h-12 w-full items-center justify-center gap-3 rounded-full border border-white/10 bg-background px-5 font-bold transition hover:border-white/20 hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"><span aria-hidden="true" class="font-display text-xl font-extrabold text-[#4285F4]">G</span> Lanjutkan dengan Google</a>
    <p class="mt-4 text-center text-sm text-foreground/60">Belum punya akun? <a class="rounded-full font-extrabold text-accent hover:underline focus-visible:outline-2 focus-visible:outline-accent" href="{{ route('register') }}">Ayo daftar</a></p>
</x-layouts.auth>
