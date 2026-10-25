<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_is_public_and_discloses_current_data_practices(): void
    {
        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertViewIs('privacy-policy')
            ->assertSee('Kebijakan privasi')
            ->assertSee('artaswaha_device')
            ->assertSee('365 hari')
            ->assertSee('alamat IP')
            ->assertSee('ID akun Google')
            ->assertSee('gateway AI')
            ->assertSee('belum ada jadwal penghapusan otomatis')
            ->assertSee('kerthajayasoftware@gmail.com')
            ->assertSee('href="'.route('home').'"', false);
    }

    public function test_policy_remains_linked_outside_login_and_registration(): void
    {
        $policyUrl = 'href="'.route('privacy-policy').'"';

        $this->get(route('home'))->assertSee($policyUrl, false);
        $this->get(route('login'))->assertDontSee($policyUrl, false);
        $this->get(route('register'))->assertDontSee($policyUrl, false);
        $this->get(route('password.request'))->assertSee($policyUrl, false);

        $path = LearningPath::factory()->create(['is_published' => true]);
        $this->post(route('learning.interview.store'), ['step' => 1, 'learning_path_ids' => [$path->id]]);
        $this->get(route('learning.interview.show', ['step' => 2]))->assertSee($policyUrl, false);
    }
}
