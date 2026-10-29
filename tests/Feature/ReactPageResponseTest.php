<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdminAnalyticsService;
use App\Services\ReactPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReactPageResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware('web')->get('/react-contract', fn (): ReactPage => ReactPage::render('Home', [
            'title' => '</script><script>alert(1)</script>',
            'unused' => fn (): string => 'expensive',
            'statistics' => fn (): array => ['count' => 4],
            'meta' => ['title' => 'Page title', 'description' => 'Page description'],
        ], ['statistics']));
    }

    public function test_html_bootstrap_escapes_script_content_and_omits_deferred_props(): void
    {
        Http::fake();
        $response = $this->get('/react-contract?tab=one')->assertOk()
            ->assertSee('<script id="react-page" type="application/json">', false)
            ->assertSee('<div id="app"></div>', false)
            ->assertDontSee('</script><script>alert(1)</script>', false);
        preg_match('/<script id="react-page" type="application\/json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $page = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('</script><script>alert(1)</script>', $page['props']['title']);
        $this->assertSame('/react-contract?tab=one', $page['url']);
        $this->assertSame(['statistics'], $page['deferred']);
        $this->assertArrayNotHasKey('statistics', $page['props']);
        Http::assertNothingSent();
    }

    public function test_partial_props_are_lazy_and_shared_identity_and_session_props_are_fresh(): void
    {
        Route::middleware('web')->get('/react-lazy', fn (): ReactPage => ReactPage::render('Home', [
            'selected' => fn (): string => 'value',
            'unused' => fn () => throw new \LogicException('Must not evaluate omitted props'),
        ]));
        $user = User::factory()->create(['name' => 'Kadek']);
        Route::middleware('web')->get('/react-error', fn () => redirect('/react-lazy')->withErrors(['title' => 'Invalid title'])->with('status', 'Saved'));
        $this->actingAs($user)->get('/react-error')->assertRedirect('/react-lazy');
        $this->get('/react-lazy', ['X-React-Page' => 'true', 'X-React-Only' => 'selected', 'X-React-Component' => 'Home'])
            ->assertOk()->assertHeader('X-React-Page', 'true')
            ->assertJsonPath('props.selected', 'value')->assertJsonPath('props.viewer.name', 'Kadek')
            ->assertJsonPath('props.errors.title', 'Invalid title')->assertJsonPath('props.flash.message', 'Saved')
            ->assertJsonPath('props.csrfToken', session()->token())->assertJsonMissingPath('props.unused')
            ->assertJsonMissingPath('props.materials');
    }

    public function test_component_mismatch_returns_a_full_page_and_optional_guard_allows_deferred_fetch(): void
    {
        $this->get('/react-contract', ['X-React-Page' => 'true', 'X-React-Only' => 'statistics', 'X-React-Component' => 'Other'])
            ->assertOk()->assertJsonPath('props.unused', 'expensive')->assertJsonMissingPath('props.statistics')
            ->assertJsonPath('deferred', ['statistics']);
        $this->get('/react-contract', ['X-React-Page' => 'true', 'X-React-Only' => ' statistics '])
            ->assertOk()->assertJsonPath('props.statistics.count', 4)->assertJsonMissingPath('props.unused')
            ->assertJsonMissingPath('deferred');
    }

    public function test_json_pages_are_not_cached_or_sent_to_ssr(): void
    {
        config(['react.ssr.enabled' => true]);
        Http::fake();
        $response = $this->get('/react-contract', ['X-React-Page' => 'true'])->assertOk()->assertReact();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('X-React-Page', $response->headers->get('Vary'));
        Http::assertNothingSent();
    }

    public function test_ssr_posts_the_page_to_loopback_and_renders_head_and_body(): void
    {
        config(['react.ssr.enabled' => true, 'react.ssr.url' => 'http://127.0.0.1:13714/render']);
        Http::fake(['127.0.0.1:13714/render' => Http::response(['head' => ['<title>SSR title</title>'], 'body' => '<h1>SSR body</h1>'])]);
        $this->get('/react-contract')->assertOk()->assertSee('<title>SSR title</title>', false)
            ->assertDontSee('<title data-react-head="title">Page title</title>', false)->assertSee('<div id="app"><h1>SSR body</h1></div>', false);
        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST' &&
            $request->url() === 'http://127.0.0.1:13714/render' && $request['component'] === 'Home' &&
            $request['url'] === '/react-contract' && $request['deferred'] === ['statistics']);
    }

    public function test_ssr_failure_logs_and_falls_back_to_client_rendering(): void
    {
        config(['react.ssr.enabled' => true]);
        Log::spy();
        Http::fake(['*' => Http::failedConnection()]);
        $this->get('/react-contract')->assertOk()->assertSee('<div id="app"></div>', false)
            ->assertSee('<title data-react-head="title">Page title</title>', false);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_malformed_ssr_payload_falls_back_without_rendering_invalid_markup(): void
    {
        config(['react.ssr.enabled' => true]);
        Log::spy();
        Http::fake(['*' => Http::response(['head' => [42], 'body' => '<h1>Invalid SSR</h1>'])]);
        $this->get('/react-contract')->assertOk()->assertSee('<div id="app"></div>', false)->assertDontSee('Invalid SSR');
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_ssr_cannot_send_private_page_props_to_a_remote_host(): void
    {
        config(['react.ssr.enabled' => true, 'react.ssr.url' => 'https://example.com/render']);
        Http::fake();
        Log::spy();
        $this->get('/react-contract')->assertOk()->assertSee('<div id="app"></div>', false);
        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_page_mutations_keep_native_validation_and_follow_redirects_as_get(): void
    {
        Route::middleware('web')->match(['POST', 'PUT', 'DELETE'], '/react-mutation', function (Request $request): RedirectResponse {
            $request->validate(['title' => ['required', 'string']]);

            return redirect('/react-contract')->with('status', 'Saved');
        });
        $headers = ['X-React-Page' => 'true', 'Accept' => 'application/json'];
        foreach (['post', 'put', 'delete'] as $method) {
            $this->{$method}('/react-mutation', [], $headers)->assertUnprocessable()->assertJsonValidationErrors('title');
            $this->{$method}('/react-mutation', ['title' => 'Valid'], $headers)->assertStatus(303)->assertRedirect('/react-contract');
            $this->get('/react-contract', $headers)->assertOk()->assertHeader('X-React-Page', 'true')->assertJsonPath('props.flash.message', 'Saved');
        }
    }

    public function test_react_admin_requests_return_json_401_for_guests_and_403_for_non_admins(): void
    {
        $headers = ['X-React-Page' => 'true', 'Accept' => 'application/json'];
        $this->get(route('admin.analytics.index'), $headers)->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
        $this->actingAs(User::factory()->create())->get(route('admin.analytics.index'), $headers)
            ->assertForbidden()->assertHeader('Content-Type', 'application/json');
    }

    public function test_failed_deferred_statistics_return_a_standard_json_500(): void
    {
        config(['app.debug' => false]);
        $this->mock(AdminAnalyticsService::class)->shouldReceive('snapshot')->once()
            ->andThrow(new \RuntimeException('Private analytics failure'));
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('admin.analytics.index'), ['X-React-Page' => 'true', 'Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('deferred', ['statistics']);
        $this->get(route('admin.analytics.index'), [
            'X-React-Page' => 'true', 'Accept' => 'application/json',
            'X-React-Only' => 'statistics', 'X-React-Component' => 'Admin/Analytics',
        ])->assertStatus(500)->assertJsonPath('message', 'Server Error')
            ->assertJsonMissingPath('props')->assertDontSee('Private analytics failure');
    }

    public function test_logout_invalidates_the_session_and_partial_responses_do_not_reuse_private_props(): void
    {
        $headers = ['X-React-Page' => 'true', 'Accept' => 'application/json'];
        $this->actingAs(User::factory()->create(['name' => 'Private account']))
            ->withSession(['private-marker' => 'private value'])->get('/react-contract', $headers)
            ->assertOk()->assertJsonPath('props.viewer.name', 'Private account');
        $sessionId = session()->getId();
        $csrfToken = session()->token();
        $this->post(route('logout'), [], $headers)->assertStatus(303);
        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrfToken, session()->token());
        $this->assertFalse(session()->has('private-marker'));
        $response = $this->get('/react-contract', [...$headers, 'X-React-Only' => 'title'])
            ->assertOk()->assertJsonPath('props.viewer', null)->assertJsonPath('props.csrfToken', session()->token())
            ->assertJsonPath('props.errors', [])->assertJsonPath('props.flash.message', null)
            ->assertDontSee('Private account');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('admin.analytics.index'), $headers)->assertUnauthorized();
    }
}
