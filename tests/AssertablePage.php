<?php

namespace Tests;

use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

class AssertablePage extends AssertableJson
{
    private string $componentName;

    public static function fromResponse(TestResponse $response): self
    {
        $page = $response->headers->get('X-React-Page') === 'true' ? $response->json() : $response->viewData('page');
        Assert::assertIsArray($page);
        Assert::assertArrayHasKey('component', $page);
        Assert::assertArrayHasKey('props', $page);
        Assert::assertArrayHasKey('url', $page);
        $assertable = new self($page['props']);
        $assertable->componentName = $page['component'];

        return $assertable;
    }

    public function component(string $component): self
    {
        Assert::assertSame($component, $this->componentName);

        return $this;
    }
}
