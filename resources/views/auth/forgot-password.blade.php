<x-layouts.app :show-footer="false" title="Pulihkan kata sandi — ARTASWAHA">
    <div class="grid flex-1 place-items-center px-5 py-10">
        <section class="w-full max-w-md rounded-card bg-surface p-6 sm:p-9">
            <a href="{{ route('home') }}" class="font-display text-xl font-bold">ARTASWAHA</a>
            <h1 class="mt-8 font-display text-3xl font-bold">Pulihkan akun</h1>
            <p class="mt-2 font-readable leading-7 text-foreground/65">Masukkan email akun. Jika terdaftar, kami akan mengirim tautan pemulihan.</p>
            @if (session('status'))<x-flash-notice tone="info" class="mt-5">{{ session('status') }}</x-flash-notice>@endif
            @if ($errors->any())<p role="alert" class="mt-5 rounded-2xl bg-danger/10 p-4 text-sm text-danger">{{ $errors->first() }}</p>@endif
            <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
                @csrf
                <label class="block text-sm font-semibold">Email
                    <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                </label>
                <button class="min-h-12 w-full rounded-full bg-primary px-6 py-3 font-bold text-white hover:brightness-110" type="submit">Kirim tautan</button>
            </form>
            <a href="{{ route('login') }}" class="mt-6 inline-block text-sm font-semibold text-accent hover:underline">Kembali ke masuk</a>
        </section>
    </div>
</x-layouts.app>
