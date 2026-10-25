@extends('admin.layout')
@section('title', 'Interpretasi statistik AI')
@section('content')
<a href="{{ route('admin.ai.index') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-accent">← Statistik AI</a>
<h1 class="mt-3 font-display text-3xl font-bold">Interpretasi statistik AI</h1>
<p class="my-3 text-sm text-foreground/60">{{ $aiReport->period_start->format('d M Y') }} – {{ $aiReport->period_end->format('d M Y') }} · snapshot {{ $aiReport->created_at->format('d M Y H:i') }}</p>
<div class="mb-5"><x-job-status :status="$aiReport->status" /></div>
<h2 class="mb-3 font-display text-lg font-bold">Data faktual yang digunakan</h2><x-ai-statistics :snapshot="$aiReport->snapshot" />
<section class="mt-6 rounded-card border border-accent/20 bg-surface p-6">
<h2 class="font-display text-xl font-bold">Interpretasi AI · perlu ditinjau</h2>
@if ($aiReport->status === 'completed' && $aiReport->result)
    <p class="mt-3 break-words text-sm leading-7">{{ $aiReport->result['summary'] }}</p>
    @foreach (['observations' => 'Observasi berdasarkan data', 'suggestions' => 'Saran untuk dipertimbangkan'] as $key => $label)
        <h3 class="mt-5 text-sm font-bold text-accent">{{ $label }}</h3><ul class="mt-2 list-disc space-y-2 pl-5 text-sm leading-6 text-foreground/75">@forelse ($aiReport->result[$key] as $item)<li class="break-words">{{ $item }}</li>@empty<li>Belum ada {{ strtolower($label) }}.</li>@endforelse</ul>
    @endforeach
    <p class="mt-5 text-xs text-foreground/50">Selesai {{ $aiReport->completed_at?->format('d M Y H:i') }}. Interpretasi AI tidak menetapkan kemampuan individu atau membuktikan sebab akibat.</p>
@else
    <p class="mt-3 text-sm leading-6 text-foreground/65">{{ in_array($aiReport->status, ['queued', 'processing']) ? 'Laporan diproses di background. Statistik tetap bisa dibaca selama menunggu.' : 'Interpretasi belum tersedia. Periksa konfigurasi layanan atau minta ulang laporan dari Statistik AI.' }}</p>
    <a href="{{ url()->current() }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent">Perbarui status</a>
@endif
</section>
@endsection