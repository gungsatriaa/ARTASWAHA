@extends('admin.layout')

@section('title', $lesson->exists ? 'Ubah pelajaran' : 'Buat pelajaran')

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('admin.lessons.index') }}" class="text-sm font-semibold text-accent">← Kembali ke pelajaran</a>
        <h1 class="mt-4 font-display text-3xl font-bold">{{ $lesson->exists ? 'Ubah pelajaran' : 'Pelajaran baru' }}</h1>
        @if ($lesson->exists && $lesson->is_published)
            <p class="mt-3 rounded-2xl bg-accent/10 p-4 text-sm text-accent">Menyimpan perubahan akan menarik pelajaran dari situs sampai ditinjau dan diterbitkan kembali.</p>
        @endif
        <form method="POST" action="{{ $lesson->exists ? route('admin.lessons.update', $lesson) : route('admin.lessons.store') }}" class="mt-7 space-y-5 rounded-card bg-surface p-5 sm:p-8">
            @csrf
            @if ($lesson->exists) @method('PUT') @endif
            <label class="block text-sm font-bold" for="learning_path_id">Jalur belajar
                <select id="learning_path_id" name="learning_path_id" required class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                    @foreach ($learningPaths as $learningPath)
                        <option value="{{ $learningPath->id }}" @selected((string) old('learning_path_id', $lesson->learning_path_id) === (string) $learningPath->id)>{{ $learningPath->group === 'paribasa' ? 'Paribasa' : 'Aksara' }} · {{ $learningPath->title }}</option>
                    @endforeach
                </select>
                @error('learning_path_id') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="title">Judul
                <input id="title" name="title" required maxlength="120" value="{{ old('title', $lesson->title) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('title') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="slug">Slug URL
                <input id="slug" name="slug" required maxlength="150" value="{{ old('slug', $lesson->slug) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('slug') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="summary">Ringkasan
                <input id="summary" name="summary" required maxlength="255" value="{{ old('summary', $lesson->summary) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('summary') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="content">Isi materi
                <textarea id="content" name="content" required rows="10" maxlength="50000" class="mt-2 w-full rounded-control border border-white/10 bg-background px-4 py-3 font-readable leading-7 text-foreground">{{ old('content', $lesson->content) }}</textarea>
                <span class="mt-1 block font-normal text-foreground/55">Gunakan teks biasa. Materi harus ditinjau sebelum dapat dilihat pengunjung.</span>
                @error('content') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="sort_order">Urutan dalam jalur
                <input id="sort_order" name="sort_order" type="number" min="0" max="65535" required value="{{ old('sort_order', $lesson->sort_order ?? 0) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('sort_order') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <button class="min-h-12 rounded-full bg-primary px-7 py-3 font-bold text-white">Simpan sebagai draf</button>
        </form>
    </div>
@endsection
