@extends('admin.layout')
@section('title', 'Detail draf kuis AI')
@section('content')
<a href="{{ route('admin.ai.index', ['tab' => 'drafts']) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-accent">← Draf kuis AI</a>
<h1 class="mt-3 font-display text-3xl font-bold">Draf kuis #{{ $quizDraft->id }}</h1><div class="mt-4"><x-job-status :status="$quizDraft->status" /></div>
<p class="mt-3 text-sm leading-6 text-foreground/60">Hasil AI harus diperiksa sebelum digunakan. Jika pemrosesan gagal, pemilik perlu mengunggah ulang dokumen; file sumber sementara sudah dibersihkan.</p>
<div class="mt-6 space-y-4">@forelse ($quizDraft->questions ?? [] as $question)<article class="rounded-card bg-surface p-5"><h2 class="break-words font-bold">{{ $loop->iteration }}. {{ $question['prompt'] }}</h2><ol class="mt-3 list-[upper-alpha] space-y-2 pl-5 text-sm">@foreach ($question['options'] as $option)<li class="break-words">{{ $option }} @if ($loop->index === $question['correct_option'])<span class="text-accent">· kunci jawaban</span>@endif</li>@endforeach</ol><p class="mt-3 break-words text-sm text-foreground/60">{{ $question['explanation'] ?? '' }}</p></article>@empty<p class="rounded-card bg-surface p-5 text-sm text-foreground/60">Belum ada soal yang berhasil dihasilkan.</p>@endforelse</div>
@endsection
