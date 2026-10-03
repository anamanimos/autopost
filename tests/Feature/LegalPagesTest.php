<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;
    public function test_terms_of_service_page_is_publicly_accessible(): void
    {
        $response = $this->get('/terms');
        $response->assertStatus(200);
        $response->assertSee('Syarat dan Ketentuan Layanan');
        $response->assertSee('Damaijaya Auto');
    }

    public function test_terms_of_service_redirect_works(): void
    {
        $response = $this->get('/terms-of-service');
        $response->assertRedirect('/terms');
    }

    public function test_privacy_policy_page_is_publicly_accessible(): void
    {
        $response = $this->get('/privacy');
        $response->assertStatus(200);
        $response->assertSee('Kebijakan Privasi');
        $response->assertSee('Data Deletion');
    }

    public function test_privacy_policy_redirect_works(): void
    {
        $response = $this->get('/privacy-policy');
        $response->assertRedirect('/privacy');
    }
}
