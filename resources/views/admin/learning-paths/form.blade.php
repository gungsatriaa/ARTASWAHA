@extends('admin.layout')

@section('title', $learningPath->exists ? 'Ubah jalur belajar' : 'Buat jalur belajar')

@section('content')
    <div class="max-w-3xl">
        <a href="{{ route('admin.learning-paths.index') }}" class="text-sm font-semibold text-accent">← Kembali ke jalur belajar</a>
        <h1 class="mt-4 font-display text-3xl font-bold">{{ $learningPath->exists ? 'Ubah jalur' : 'Jalur belajar baru' }}</h1>
        <form method="POST" action="{{ $learningPath->exists ? route('admin.learning-paths.update', $learningPath) : route('admin.learning-paths.store') }}" class="mt-7 space-y-5 rounded-card bg-surface p-5 sm:p-8">
            @csrf
            @if ($learningPath->exists) @method('PUT') @endif
            <label class="block text-sm font-bold" for="group">Kategori
                <select id="group" name="group" required class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                    <option value="paribasa" @selected(old('group', $learningPath->group) === 'paribasa')>Paribasa Bali</option>
                    <option value="aksara" @selected(old('group', $learningPath->group) === 'aksara')>Aksara Bali</option>
                </select>
                @error('group') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="title">Nama jalur
                <input id="title" name="title" required maxlength="120" value="{{ old('title', $learningPath->title) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('title') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="slug">Slug URL
                <input id="slug" name="slug" required maxlength="150" value="{{ old('slug', $learningPath->slug) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('slug') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="description">Deskripsi
                <textarea id="description" name="description" required rows="3" maxlength="1000" class="mt-2 w-full rounded-control border border-white/10 bg-background px-4 py-3 text-foreground">{{ old('description', $learningPath->description) }}</textarea>
                @error('description') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="sort_order">Urutan
                <input id="sort_order" name="sort_order" type="number" min="0" max="65535" required value="{{ old('sort_order', $learningPath->sort_order ?? 0) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('sort_order') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="flex items-center gap-3 text-sm font-semibold">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $learningPath->is_published)) class="size-5 accent-[var(--color-primary)]">
                Tampilkan jalur ini di situs
            </label>
            <button class="min-h-12 rounded-full bg-primary px-7 py-3 font-bold text-white">Simpan jalur</button>
        </form>
    </div>
@endsection
