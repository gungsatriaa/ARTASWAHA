<x-layouts.app :show-footer="false" title="Masuk — ARTASWAHA">
    <div class="grid flex-1 place-items-center px-5 py-10">
        <section class="w-full max-w-md rounded-card bg-surface p-6 sm:p-9">
            <a href="{{ route('home') }}" class="font-display text-xl font-bold">ARTASWAHA</a>
            <h1 class="mt-8 font-display text-3xl font-bold">Senang bertemu lagi!</h1>
            <p class="mt-2 font-readable text-foreground/65">Masuk untuk membuat atau bergabung ke kuis.</p>
            @if (session('status'))<p role="status" class="mt-5 rounded-2xl bg-primary/20 p-4 text-sm text-accent">{{ session('status') }}</p>@endif
            @if ($errors->any())<p role="alert" class="mt-5 rounded-2xl bg-danger/10 p-4 text-sm text-danger">{{ $errors->first() }}</p>@endif
            <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
                @csrf
                <label class="block text-sm font-semibold">Email
                    <input name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground outline-none focus:border-accent">
                </label>
                <label class="block text-sm font-semibold">Kata sandi
                    <input name="password" type="password" required autocomplete="current-password" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground outline-none focus:border-accent">
                </label>
                <label class="flex items-center gap-3 text-sm text-foreground/75"><input name="remember" type="checkbox" value="1" class="size-4 accent-[var(--color-accent)]"> Ingat saya</label>
                <button class="min-h-12 w-full rounded-full bg-primary px-6 py-3 font-bold text-white hover:brightness-110" type="submit">Masuk</button>
            </form>
            <a href="{{ route('password.request') }}" class="mt-5 inline-block text-sm text-accent hover:underline">Lupa kata sandi?</a>
            <p class="mt-6 text-sm text-foreground/65">Belum punya akun? <a class="font-bold text-accent hover:underline" href="{{ route('register') }}">Daftar</a></p>
        </section>
    </div>
</x-layouts.app>
