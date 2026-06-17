<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_route_redirects_to_home_with_login_modal_flag(): void
    {
        $this->get('/login')
            ->assertRedirect('/?auth=login');
    }

    public function test_register_route_redirects_to_home_with_register_modal_flag(): void
    {
        $this->get('/register')
            ->assertRedirect('/?auth=register');
    }
}
