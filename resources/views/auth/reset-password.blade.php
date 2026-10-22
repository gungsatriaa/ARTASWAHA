<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Atur ulang kata sandi — ARTASWAHA</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="grid min-h-screen place-items-center bg-background px-5 py-10 font-sans text-foreground">
        <main class="w-full max-w-md rounded-card bg-surface p-7 sm:p-9">
            <a href="{{ route('home') }}" class="font-display text-xl font-bold">ARTASWAHA</a>
            <h1 class="mt-8 font-display text-3xl font-bold">Atur kata sandi baru</h1>
            @if ($errors->any())<p role="alert" class="mt-5 rounded-2xl bg-danger/10 p-4 text-sm text-danger">{{ $errors->first() }}</p>@endif
            <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <label class="block text-sm font-semibold">Email
                    <input name="email" type="email" value="{{ old('email', request('email')) }}" required autocomplete="email" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                </label>
                <label class="block text-sm font-semibold">Kata sandi baru
                    <input name="password" type="password" required minlength="8" autocomplete="new-password" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                </label>
                <label class="block text-sm font-semibold">Ulangi kata sandi
                    <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                </label>
                <button class="min-h-12 w-full rounded-full bg-primary px-6 py-3 font-bold text-white hover:brightness-110" type="submit">Perbarui kata sandi</button>
            </form>
        </main>
    </body>
</html>
