@extends('admin.layout')

@section('title', $lesson->exists ? 'Ubah pelajaran' : 'Buat pelajaran')

@section('content')
    @php
        $hasExercise = (bool) old('has_exercise', $lesson->exercise_prompt !== null);
        $exerciseOptions = old('exercise_options', $lesson->exercise_options ?? ['', '', '', '']);
    @endphp
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
            <section class="border-t border-white/10 pt-5">
                <label class="flex items-center gap-3 text-sm font-bold">
                    <input type="checkbox" name="has_exercise" value="1" data-exercise-toggle @checked($hasExercise) class="size-5 accent-[var(--color-accent)]">
                    Tambahkan latihan pilihan ganda
                </label>
                <fieldset data-exercise-fields @disabled(! $hasExercise) class="mt-5 space-y-4 disabled:opacity-50">
                    <legend class="font-display text-lg font-bold">Latihan setelah materi</legend>
                    <label class="block text-sm font-bold" for="exercise_prompt">Pertanyaan latihan
                        <textarea id="exercise_prompt" name="exercise_prompt" rows="2" maxlength="500" required @disabled(! $hasExercise) class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 text-foreground">{{ old('exercise_prompt', $lesson->exercise_prompt) }}</textarea>
                        @error('exercise_prompt') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
                    </label>
                    <p class="text-sm text-foreground/65">Isi empat pilihan, lalu pilih satu jawaban benar.</p>
                    @foreach (range(0, 3) as $option)
                        <label class="flex items-center gap-3 rounded-2xl bg-background p-3">
                            <input type="radio" name="correct_option" value="{{ $option }}" required @checked((string) old('correct_option', $lesson->correct_option) === (string) $option) @disabled(! $hasExercise) class="accent-[var(--color-accent)]">
                            <input name="exercise_options[]" required maxlength="140" value="{{ $exerciseOptions[$option] ?? '' }}" aria-label="Pilihan jawaban {{ $option + 1 }}" placeholder="Pilihan {{ $option + 1 }}" @disabled(! $hasExercise) class="min-h-10 min-w-0 flex-1 bg-transparent outline-none">
                        </label>
                    @endforeach
                    @error('exercise_options') <span class="block text-danger">{{ $message }}</span> @enderror
                    @error('correct_option') <span class="block text-danger">{{ $message }}</span> @enderror
                    <label class="block text-sm font-bold" for="exercise_explanation">Penjelasan jawaban <span class="font-normal text-foreground/55">(opsional)</span>
                        <textarea id="exercise_explanation" name="exercise_explanation" rows="2" maxlength="500" @disabled(! $hasExercise) class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 text-foreground">{{ old('exercise_explanation', $lesson->exercise_explanation) }}</textarea>
                    </label>
                </fieldset>
            </section>
            <button class="min-h-12 rounded-full bg-primary px-7 py-3 font-bold text-white">Simpan sebagai draf</button>
        </form>
    </div>
@endsection
