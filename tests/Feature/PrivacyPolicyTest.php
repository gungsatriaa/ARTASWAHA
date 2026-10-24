<?php

namespace Tests\Feature;

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

    public function test_policy_is_linked_where_users_provide_data(): void
    {
        $policyUrl = 'href="'.route('privacy-policy').'"';

        $this->get(route('home'))->assertSee($policyUrl, false);
        $this->get(route('login'))->assertSee($policyUrl, false);
        $this->get(route('register'))->assertSee($policyUrl, false);
        $this->get(route('password.request'))->assertSee($policyUrl, false);
        $this->get(route('learning.interview.show'))->assertSee($policyUrl, false);
    }
}
