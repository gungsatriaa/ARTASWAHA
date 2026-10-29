<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_have_specific_metadata_and_private_pages_are_not_indexed(): void
    {
        $learningPath = LearningPath::factory()->create(['title' => 'Cecimpedan', 'description' => 'Belajar teka-teki tradisional Bali.']);
        $lesson = Lesson::factory()->create(['learning_path_id' => $learningPath->id, 'title' => 'Teka-teki Bali', 'summary' => 'Kenali jawaban teka-teki Bali.']);

        $this->get(route('home'))->assertRedirectToRoute('learning.interview.show');
        $this->actingAs(User::factory()->create())->get(route('home'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('home').'" data-react-head="canonical">', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertDontSee('name="robots" content="noindex, nofollow"', false);

        $this->get(route('learning.paths.show', $learningPath->slug))
            ->assertOk()
            ->assertSee('<meta name="description" content="Belajar teka-teki tradisional Bali." data-react-head="description">', false)
            ->assertSee('<link rel="canonical" href="'.route('learning.paths.show', $learningPath->slug).'" data-react-head="canonical">', false);

        $this->get(route('learning.lessons.show', $lesson->slug))
            ->assertOk()
            ->assertSee('<meta name="description" content="Kenali jawaban teka-teki Bali." data-react-head="description">', false)
            ->assertSee('<link rel="canonical" href="'.route('learning.lessons.show', $lesson->slug).'" data-react-head="canonical">', false);

        $this->get(route('quizzes.index'))->assertOk()->assertSee('<link rel="canonical" href="'.route('quizzes.index').'" data-react-head="canonical">', false);
        $this->get(route('privacy-policy'))->assertOk()->assertSee('<link rel="canonical" href="'.route('privacy-policy').'" data-react-head="canonical">', false);
        $this->post(route('logout'));
        $this->get(route('login'))->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false);
        $this->get(route('learning.interview.show'))->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_sitemap_only_lists_published_learning_content(): void
    {
        $publishedPath = LearningPath::factory()->create();
        $hiddenPath = LearningPath::factory()->create(['is_published' => false]);
        $publishedLesson = Lesson::factory()->create(['learning_path_id' => $publishedPath->id]);
        $unreviewedLesson = Lesson::factory()->create(['learning_path_id' => $publishedPath->id, 'reviewed_at' => null]);
        $hiddenLesson = Lesson::factory()->create(['learning_path_id' => $publishedPath->id, 'is_published' => false]);
        $lessonInHiddenPath = Lesson::factory()->create(['learning_path_id' => $hiddenPath->id]);

        $response = $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'))
            ->assertSee(route('privacy-policy'))
            ->assertSee(route('learning.paths.show', $publishedPath->slug))
            ->assertSee(route('learning.lessons.show', $publishedLesson->slug))
            ->assertDontSee(route('learning.paths.show', $hiddenPath->slug))
            ->assertDontSee(route('learning.lessons.show', $unreviewedLesson->slug))
            ->assertDontSee(route('learning.lessons.show', $hiddenLesson->slug))
            ->assertDontSee(route('learning.lessons.show', $lessonInHiddenPath->slug));

        $this->assertNotFalse(simplexml_load_string($response->getContent()));
        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: '.route('sitemap'));
    }
}
