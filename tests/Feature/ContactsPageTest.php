<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_page_is_available(): void
    {
        $response = $this->get('/contacts');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contacts')
                ->where('contacts.telegram', '@food_delivery'));
    }
}
