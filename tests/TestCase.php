<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['react.ssr.enabled' => false]);

        TestResponse::macro('assertReact', function (?callable $callback = null): TestResponse {
            $page = AssertablePage::fromResponse($this);
            if ($callback !== null) {
                $callback($page);
            }

            return $this;
        });
        TestResponse::macro('assertReactContains', function (string $text): TestResponse {
            $this->assertReact(fn (AssertablePage $page) => $page->etc());
            Assert::assertStringContainsString($text, json_encode(($this->headers->get('X-React-Page') === 'true' ? $this->json() : $this->viewData('page'))['props'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $this;
        });
        TestResponse::macro('assertReactNotContains', function (string $text): TestResponse {
            $this->assertReact(fn (AssertablePage $page) => $page->etc());
            Assert::assertStringNotContainsString($text, json_encode(($this->headers->get('X-React-Page') === 'true' ? $this->json() : $this->viewData('page'))['props'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $this;
        });
        TestResponse::macro('assertReactValue', function (string $key, mixed $value): TestResponse {
            return $this->assertReact(fn (AssertablePage $page) => $page->where($key, $value));
        });
    }
}
