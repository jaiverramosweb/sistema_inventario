<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesInventoryFixtures;
use Tests\TestCase;

class AuthLogoutFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CreatesInventoryFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_user_can_logout_with_valid_token(): void
    {
        $user = $this->createApiUser();
        $headers = $this->authHeadersFor($user);

        $response = $this->withHeaders($headers)->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Successfully logged out',
            ]);

        $this->withHeaders($headers)
            ->postJson('/api/auth/me')
            ->assertUnauthorized();
    }
}
