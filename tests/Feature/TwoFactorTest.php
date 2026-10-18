<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Users\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function login(User $user): void
    {
        $this->actingAs($user);

        // Confirm the user's password so Fortify's `password.confirm`
        // middleware passes for the two-factor endpoints.
        $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    public function test_2fa_endpoints_require_password_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)
            ->postJson(route('two-factor.enable'))
            ->assertStatus(423);
    }

    public function test_user_can_enable_confirm_and_disable_two_factor(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $this->login($user);

        // 1. Enable generates the secret + recovery codes.
        $this->postJson(route('two-factor.enable'))->assertOk();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);

        // 2. Confirm with a valid TOTP code derived from the secret.
        $google2fa = new Google2FA;
        $code = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->postJson(route('two-factor.confirm'), ['code' => $code])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);

        // 3. Recovery codes are available.
        $this->getJson(route('two-factor.recovery-codes'))
            ->assertOk()
            ->assertJsonCount(8);

        // 4. Disable clears the secret.
        $this->deleteJson(route('two-factor.disable'))->assertOk();

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
    }

    public function test_two_factor_confirm_rejects_invalid_code(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $this->login($user);

        $this->postJson(route('two-factor.enable'))->assertOk();

        $this->postJson(route('two-factor.confirm'), ['code' => '000000'])
            ->assertStatus(422);

        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $this->login($user);

        $this->postJson(route('two-factor.enable'))->assertOk();

        $before = $this->getJson(route('two-factor.recovery-codes'))->json();

        $this->postJson(route('two-factor.regenerate-recovery-codes'))->assertOk();

        $after = $this->getJson(route('two-factor.recovery-codes'))->json();

        $this->assertNotSame($before, $after);
    }
}
