<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_is_public_and_uses_the_react_page(): void
    {
        $this->get(route('privacy-policy'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Privacy')->where('meta.robots', 'index, follow'));
    }

    public function test_privacy_route_is_shared_without_duplicating_policy_copy_in_auth(): void
    {
        foreach (['home', 'login', 'register', 'password.request', 'learning.interview.show'] as $route) {
            $this->get(route($route))->assertOk()->assertInertiaValue('navigation.privacy', route('privacy-policy'));
        }
    }
}
