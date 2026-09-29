<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Signup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_redirects_to_signup(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('signup'));
    }

    public function test_signup_screen_can_be_rendered(): void
    {
        $response = $this->get('/signup');

        $response->assertOk()
            ->assertSeeLivewire(Signup::class);
    }

    public function test_signup_validates_required_fields(): void
    {
        Livewire::test(Signup::class)
            ->call('signup')
            ->assertHasErrors(['name', 'email', 'password', 'company_name']);
    }
}
