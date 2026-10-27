<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Assert;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        TestResponse::macro('assertInertiaContains', function (string $text): TestResponse {
            $this->assertInertia(fn (AssertableInertia $page) => $page->etc());
            Assert::assertStringContainsString($text, json_encode($this->viewData('page')['props'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $this;
        });
        TestResponse::macro('assertInertiaNotContains', function (string $text): TestResponse {
            $this->assertInertia(fn (AssertableInertia $page) => $page->etc());
            Assert::assertStringNotContainsString($text, json_encode($this->viewData('page')['props'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return $this;
        });
        TestResponse::macro('assertInertiaValue', function (string $key, mixed $value): TestResponse {
            return $this->assertInertia(fn (AssertableInertia $page) => $page->where($key, $value));
        });
    }
}
