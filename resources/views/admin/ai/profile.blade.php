@extends('admin.layout')
@section('title', 'Detail analisis interview')
@section('content')
<a href="{{ route('admin.ai.index', ['tab' => 'interviews']) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-accent">← Analisis interview</a>
<h1 class="mt-3 font-display text-3xl font-bold">Pelajar #{{ $learnerProfile->id }}</h1>
<div class="my-5 rounded-card border border-white/10 bg-surface p-5"><h2 class="font-bold">Dasar rekomendasi</h2><dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2"><div><dt class="text-foreground/50">Peran</dt><dd class="mt-1">{{ $analysis['role'] }}</dd></div><div><dt class="text-foreground/50">Jenis profil</dt><dd class="mt-1">{{ $learnerProfile->mode === 'account' ? 'Akun' : 'Anonim' }}</dd></div><div><dt class="text-foreground/50">Materi pilihan</dt><dd class="mt-1">{{ $paths->pluck('title')->join(', ') ?: 'Materi sudah tidak tersedia' }}</dd></div><div><dt class="text-foreground/50">Analisis selesai</dt><dd class="mt-1">{{ $learnerProfile->analysis_completed_at?->format('d M Y H:i') ?? 'Belum selesai' }}</dd></div></dl><div class="mt-4"><x-job-status :status="$analysis['status']" /></div></div>
<x-learner-analysis :analysis="$analysis" :poll="false" :retry-url="route('admin.ai.profiles.retry', $learnerProfile)" />
<a href="{{ url()->current() }}" class="inline-flex min-h-11 items-center text-sm font-bold text-accent">Perbarui tampilan status</a>
@endsection