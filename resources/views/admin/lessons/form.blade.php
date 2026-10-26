@extends('admin.layout')

@section('title', $lesson->exists ? 'Ubah modul' : 'Buat modul')

@section('content')
    @php
        $hasExercise = (bool) old('has_exercise', $lesson->exercise_prompt !== null);
        $exerciseOptions = old('exercise_options', $lesson->exercise_options ?? ['', '', '', '']);
    @endphp
    <div class="max-w-3xl">
        <a href="{{ route('admin.lessons.index') }}" class="text-sm font-semibold text-accent">← Kembali ke modul</a>
        <h1 class="mt-4 font-display text-3xl font-bold">{{ $lesson->exists ? 'Ubah modul' : 'Modul baru' }}</h1>
        @if ($lesson->exists && $lesson->is_published)
            <p class="mt-3 rounded-2xl bg-accent/10 p-4 text-sm text-accent">Menyimpan perubahan akan menarik modul dari situs sampai ditinjau dan diterbitkan kembali.</p>
        @endif
        <ol class="mt-5 flex flex-wrap gap-3 text-xs font-bold text-foreground/55" aria-label="Alur modul"><li>1. Pilih jalur</li><li class="text-accent" aria-current="step">2. Isi modul</li><li>3. Preview & tinjau</li><li>4. Terbitkan</li></ol>
        @if ($learningPaths->isEmpty())
            <div class="mt-6 rounded-card border border-accent/25 bg-surface p-6"><p class="font-bold">Buat jalur belajar terlebih dahulu.</p><p class="mt-2 text-sm text-foreground/60">Setiap modul perlu berada dalam satu jalur agar urutan belajarnya jelas.</p><a href="{{ route('admin.learning-paths.create') }}" class="mt-4 inline-flex min-h-11 items-center rounded-full bg-accent px-5 font-bold text-background">Buat jalur belajar</a></div>
        @else
        <form method="POST" action="{{ $lesson->exists ? route('admin.lessons.update', $lesson) : route('admin.lessons.store') }}" class="mt-7 space-y-5 rounded-card bg-surface p-5 sm:p-8" data-aksara-scope>
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
                <input id="title" data-aksara-input data-aksara-label="Judul" name="title" required maxlength="120" value="{{ old('title', $lesson->title) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('title') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="slug">Slug URL
                <input id="slug" name="slug" placeholder="Otomatis dari judul jika dikosongkan" maxlength="150" value="{{ old('slug', $lesson->slug) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('slug') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="summary">Ringkasan
                <input id="summary" data-aksara-input data-aksara-label="Ringkasan" name="summary" required maxlength="255" value="{{ old('summary', $lesson->summary) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('summary') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="content">Isi materi
                <textarea id="content" data-aksara-input data-aksara-label="Isi materi" name="content" required rows="10" maxlength="50000" class="mt-2 w-full rounded-control border border-white/10 bg-background px-4 py-3 font-readable leading-7 text-foreground">{{ old('content', $lesson->content) }}</textarea>
                <span class="mt-1 block font-normal text-foreground/55">Gunakan teks biasa. Materi harus ditinjau sebelum dapat dilihat pengunjung.</span>
                @error('content') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm font-bold" for="sort_order">Urutan dalam jalur
                <input id="sort_order" name="sort_order" type="number" min="0" max="65535" required value="{{ old('sort_order', $lesson->sort_order ?? 0) }}" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 text-foreground">
                @error('sort_order') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
            </label>
            <label class="flex items-start gap-3 text-sm font-bold">
                <input type="hidden" name="keyboard_auto_open" value="0">
                <input type="checkbox" name="keyboard_auto_open" value="1" @checked(old('keyboard_auto_open', $lesson->keyboard_auto_open)) class="mt-1 size-5 shrink-0 accent-[var(--color-accent)]">
                <span>Buka keyboard aksara otomatis untuk pelajar<span class="mt-1 block font-normal text-foreground/60">Keyboard langsung terbuka pada latihan modul ini, atau setelah materi jika tidak ada latihan. Pelajar tetap dapat menutupnya.</span></span>
            </label>
            @error('keyboard_auto_open') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <section class="border-t border-white/10 pt-5">
                <label class="flex items-center gap-3 text-sm font-bold">
                    <input type="checkbox" name="has_exercise" value="1" data-exercise-toggle @checked($hasExercise) class="size-5 accent-[var(--color-accent)]">
                    Tambahkan latihan pilihan ganda
                </label>
                <fieldset data-exercise-fields @disabled(! $hasExercise) class="mt-5 space-y-4 disabled:opacity-50">
                    <legend class="font-display text-lg font-bold">Latihan setelah materi</legend>
                    <label class="block text-sm font-bold" for="exercise_prompt">Pertanyaan latihan
                        <textarea id="exercise_prompt" data-aksara-input data-aksara-label="Pertanyaan latihan" name="exercise_prompt" rows="2" maxlength="500" required @disabled(! $hasExercise) class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 text-foreground">{{ old('exercise_prompt', $lesson->exercise_prompt) }}</textarea>
                        @error('exercise_prompt') <span class="mt-1 block text-danger">{{ $message }}</span> @enderror
                    </label>
                    <p class="text-sm text-foreground/65">Isi empat pilihan, lalu pilih satu jawaban benar.</p>
                    @foreach (range(0, 3) as $option)
                        <label class="flex items-center gap-3 rounded-2xl bg-background p-3">
                            <input type="radio" name="correct_option" value="{{ $option }}" required @checked((string) old('correct_option', $lesson->correct_option) === (string) $option) @disabled(! $hasExercise) class="accent-[var(--color-accent)]">
                            <input data-aksara-input data-aksara-label="Pilihan jawaban" name="exercise_options[]" required maxlength="140" value="{{ $exerciseOptions[$option] ?? '' }}" aria-label="Pilihan jawaban {{ $option + 1 }}" placeholder="Pilihan {{ $option + 1 }}" @disabled(! $hasExercise) class="min-h-10 min-w-0 flex-1 bg-transparent outline-none">
                        </label>
                    @endforeach
                    @error('exercise_options') <span class="block text-danger">{{ $message }}</span> @enderror
                    @error('correct_option') <span class="block text-danger">{{ $message }}</span> @enderror
                    <label class="block text-sm font-bold" for="exercise_explanation">Penjelasan jawaban <span class="font-normal text-foreground/55">(opsional)</span>
                        <textarea id="exercise_explanation" data-aksara-input data-aksara-label="Penjelasan jawaban" name="exercise_explanation" rows="2" maxlength="500" @disabled(! $hasExercise) class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 text-foreground">{{ old('exercise_explanation', $lesson->exercise_explanation) }}</textarea>
                    </label>
                </fieldset>
            </section>
            <button class="min-h-12 rounded-full bg-primary px-7 py-3 font-bold text-white">Simpan sebagai draf</button>
            <x-aksara-keyboard :scratch="false" />
        </form>
        @if ($lesson->exists)
            <div class="mt-4 flex flex-wrap gap-4"><a href="{{ route('admin.lessons.preview', $lesson) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-accent">Preview modul tersimpan ?</a><a href="{{ route('admin.audio.lessons.edit', $lesson) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-accent">Siapkan audio</a></div>
        @endif
        @endif
    </div>
@endsection
