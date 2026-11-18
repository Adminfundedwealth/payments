<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class MerchantAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'https://payments.fundedwealth.com/auth/google/callback',
        ]);
    }

    public function test_registration_creates_a_hashed_active_merchant_and_credentials(): void
    {
        config(['auth.allow_registration' => true]);
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Full name')
            ->assertSee('Business / merchant name')
            ->assertSee('Continue with Google');

        $response = $this->post(route('register'), [
            'name' => 'Merchant Owner',
            'business_name' => 'Example Store',
            'email' => ' OWNER@EXAMPLE.COM ',
            'phone' => '+91 98765-43210',
            'password' => 'merchant-password-1',
            'password_confirmation' => 'merchant-password-1',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $response->assertRedirect(route('accounts.create'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('merchant', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('merchant-password-1', $user->password));
        $this->assertStringStartsWith('key_', $user->api_key);
        $this->assertStringStartsWith('sec_', $user->api_secret);
        $response->assertDontSee($user->api_key)->assertDontSee($user->api_secret);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_registration_rejects_duplicate_email_case_insensitively(): void
    {
        config(['auth.allow_registration' => true]);
        User::create([
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => 'existing-password',
        ]);

        $this->post(route('register'), [
            'name' => 'New Merchant',
            'business_name' => 'New Store',
            'email' => 'EXISTING@example.com',
            'password' => 'merchant-password-1',
            'password_confirmation' => 'merchant-password-1',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_validates_email_password_confirmation_and_required_fields(): void
    {
        config(['auth.allow_registration' => true]);

        $this->post(route('register'), [])
            ->assertSessionHasErrors(['name', 'business_name', 'email', 'password']);

        $valid = [
            'name' => 'Merchant',
            'business_name' => 'Store',
            'email' => 'merchant@example.com',
            'password' => 'merchant-password-1',
            'password_confirmation' => 'merchant-password-1',
        ];

        $this->post(route('register'), array_merge($valid, ['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
        $this->post(route('register'), array_merge($valid, ['password' => 'weak', 'password_confirmation' => 'weak']))
            ->assertSessionHasErrors('password');
        $this->post(route('register'), array_merge($valid, ['password_confirmation' => 'different-password']))
            ->assertSessionHasErrors('password');
        $this->post(route('register'), array_merge($valid, ['phone' => '---....']))
            ->assertSessionHasErrors('phone');
        $this->post(route('register'), array_merge($valid, ['password' => ['invalid']]))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_disabled_registration_shows_a_notice_and_does_not_create_users(): void
    {
        config(['auth.allow_registration' => false]);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('registration is temporarily unavailable')
            ->assertDontSee('name="business_name"', false);

        $this->post(route('register'), [
            'name' => 'Merchant',
            'business_name' => 'Store',
            'email' => 'merchant@example.com',
            'password' => 'merchant-password-1',
            'password_confirmation' => 'merchant-password-1',
        ])->assertRedirect(route('register'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_is_rate_limited(): void
    {
        config(['auth.allow_registration' => true]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->post(route('register'), [])->assertRedirect();
        }

        $this->post(route('register'), [])->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_login_regenerates_the_session_and_rejects_disabled_users(): void
    {
        $user = User::create([
            'name' => 'Merchant',
            'email' => 'merchant@example.com',
            'password' => 'merchant-password-1',
        ]);

        $this->get(route('login'));
        $sessionId = session()->getId();

        $this->post(route('login'), [
            'email' => 'MERCHANT@example.com',
            'password' => 'merchant-password-1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());

        Auth::logout();
        $this->post(route('login'), [
            'email' => 'merchant@example.com',
            'password' => ['invalid'],
        ])->assertSessionHasErrors('password');

        $user->update(['is_active' => false]);
        $this->post(route('login'), [
            'email' => 'merchant@example.com',
            'password' => 'merchant-password-1',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_redirect_uses_session_state_and_unmatched_state_is_rejected(): void
    {
        $this->get(route('google.redirect'))->assertRedirect();
        $state = session('state');
        $this->assertIsString($state);
        $this->assertNotSame('', $state);

        $this->get(route('google.callback', ['state' => 'invalid-state', 'code' => 'test-code']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_registration_creates_a_verified_merchant_and_allows_dashboard_access(): void
    {
        config(['auth.allow_registration' => true]);
        $this->mockGoogleUser('google-merchant-1', 'newmerchant@example.com');

        $response = $this->get(route('google.callback'));
        $user = User::where('email', 'newmerchant@example.com')->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('merchant', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertSame('google-merchant-1', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertStringStartsWith('key_', $user->api_key);
        $this->assertStringStartsWith('sec_', $user->api_secret);
        $response->assertDontSee($user->api_key)->assertDontSee($user->api_secret);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_google_verified_email_links_to_existing_merchant_without_duplicate(): void
    {
        $user = User::create([
            'name' => 'Existing Merchant',
            'business_name' => 'Existing Store',
            'email' => 'link@example.com',
            'password' => 'merchant-password-1',
        ]);
        $this->mockGoogleUser('google-link-1', 'LINK@example.com');

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-link-1', $user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_cannot_link_admin_or_disabled_merchants(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'admin-password-1',
            'role' => 'admin',
        ]);
        $this->mockGoogleUser('google-admin', $admin->email);

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertNull($admin->fresh()->google_id);

        $disabled = User::create([
            'name' => 'Disabled',
            'email' => 'disabled@example.com',
            'password' => 'merchant-password-1',
            'is_active' => false,
        ]);
        $this->mockGoogleUser('google-disabled', $disabled->email);

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_google_registration_requires_enabled_signups_and_verified_email(): void
    {
        $this->mockGoogleUser('google-closed', 'closed@example.com');
        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        $this->assertDatabaseCount('users', 0);

        config(['auth.allow_registration' => true]);
        $this->mockGoogleUser('google-unverified', 'unverified@example.com', ['email_verified' => false]);
        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_google_account_cannot_be_used_with_a_different_email(): void
    {
        $user = User::create([
            'name' => 'Linked Merchant',
            'email' => 'linked@example.com',
            'password' => 'merchant-password-1',
        ]);
        $user->forceFill(['google_id' => 'google-linked-id'])->save();
        $this->mockGoogleUser('google-linked-id', 'different@example.com');

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_authenticated_merchant_can_log_out(): void
    {
        $user = User::create([
            'name' => 'Merchant',
            'email' => 'merchant@example.com',
            'password' => 'merchant-password-1',
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    private function mockGoogleUser(string $id, string $email, array $overrides = []): void
    {
        $attributes = array_merge([
            'id' => $id,
            'name' => 'Google Merchant',
            'email' => $email,
            'email_verified' => true,
        ], $overrides);

        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn(GoogleUser::fake($attributes));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
