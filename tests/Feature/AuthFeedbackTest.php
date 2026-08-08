<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Users\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_login_rejects_invalid_credentials_with_error_feedback(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'password' => 'correct-password']);

        $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_redirects_on_success(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'password' => 'correct-password']);

        $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'correct-password',
        ])->assertRedirect();
    }

    public function test_register_rejects_duplicate_email_with_field_error(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->post(route('register.store'), [
            'name' => 'New User',
            'email' => 'dup@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_forgot_password_reports_success_for_unregistered_email(): void
    {
        // Anti user-enumeration: the response must not expose whether the
        // account exists, so an unregistered email still reports success.
        $this->post(route('password.email'), [
            'email' => 'nobody@example.com',
        ])->assertSessionHas('status');
    }

    public function test_forgot_password_reports_success_for_registered_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->post(route('password.email'), [
            'email' => 'existing@example.com',
        ])->assertSessionHas('status');
    }
}
