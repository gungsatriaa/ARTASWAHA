@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc></url>
    <url><loc>{{ route('quizzes.index') }}</loc></url>
    <url><loc>{{ route('privacy-policy') }}</loc></url>
    @foreach ($learningPaths as $learningPath)
        <url><loc>{{ route('learning.paths.show', $learningPath->slug) }}</loc></url>
        @foreach ($learningPath->lessons as $lesson)
            <url><loc>{{ route('learning.lessons.show', $lesson->slug) }}</loc></url>
        @endforeach
    @endforeach
</urlset>
