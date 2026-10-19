<?php

namespace Tests\Feature;

use App\Enums\TwoFactorMethod;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Settings;
use App\Livewire\Staff\StaffForm;
use App\Livewire\TwoFactorSettings;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use App\Support\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        User::factory()->admin()->create(); // the app is installed
    }

    protected function login(User $user): void
    {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login');
    }

    protected function emailedCode(User $user): string
    {
        $code = null;
        Notification::assertSentTo($user, TwoFactorCode::class, function ($notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        return $code;
    }

    public function test_people_without_two_factor_sign_in_with_just_a_password(): void
    {
        $user = User::factory()->create();

        $this->login($user);

        $this->assertAuthenticatedAs($user);
        Notification::assertNotSentTo($user, TwoFactorCode::class);
    }

    public function test_when_required_everyone_gets_an_email_code_by_default(): void
    {
        settings()->set(['two_factor_required' => true]);
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        $this->assertSame(TwoFactorMethod::Email, $user->twoFactorMethod());

        Livewire::test(TwoFactorChallenge::class)
            ->set('code', $this->emailedCode($user))
            ->call('verify')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_codes_are_rejected_and_limited(): void
    {
        settings()->set(['two_factor_required' => true]);
        $user = User::factory()->create();
        $this->login($user);

        $challenge = Livewire::test(TwoFactorChallenge::class);

        for ($i = 0; $i < 5; $i++) {
            $challenge->set('code', '000000')->call('verify')->assertHasErrors('code');
        }

        $challenge->set('code', $this->emailedCode($user))->call('verify')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticator_app_codes_work_once(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey(32);
        $user = User::factory()->create([
            'two_factor_method' => TwoFactorMethod::App,
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['abcde-12345'],
        ]);

        $this->login($user);
        Notification::assertNotSentTo($user, TwoFactorCode::class);

        $code = $google2fa->getCurrentOtp($secret);
        Livewire::test(TwoFactorChallenge::class)->set('code', $code)->call('verify')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // The same code can't be used again.
        auth()->logout();
        $this->login($user);
        Livewire::test(TwoFactorChallenge::class)->set('code', $code)->call('verify')->assertHasErrors('code');
    }

    public function test_recovery_codes_work_once(): void
    {
        $user = User::factory()->create([
            'two_factor_method' => TwoFactorMethod::App,
            'two_factor_secret' => (new Google2FA)->generateSecretKey(32),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => ['abcde-12345', 'fghij-67890'],
        ]);

        $this->login($user);
        Livewire::test(TwoFactorChallenge::class)
            ->call('toggleRecovery')
            ->set('code', 'abcde-12345')
            ->call('verify')
            ->assertRedirect(route('dashboard'));

        $this->assertSame(['fghij-67890'], $user->fresh()->two_factor_recovery_codes);
    }

    public function test_signed_in_people_must_verify_once_two_factor_becomes_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertOk();

        settings()->set(['two_factor_required' => true]);

        $this->actingAs($user)->get('/')->assertRedirect(route('two-factor.challenge'));
        Notification::assertSentTo($user, TwoFactorCode::class);
    }

    public function test_trusted_devices_skip_the_code(): void
    {
        settings()->set(['two_factor_required' => true]);
        $user = User::factory()->create();
        $twoFactor = app(TwoFactor::class);

        $twoFactor->trustDevice($user);
        $cookie = collect(Cookie::getQueuedCookies())->firstWhere(fn ($c) => $c->getName() === TwoFactor::TRUST_COOKIE);

        $request = Request::create('/', cookies: [TwoFactor::TRUST_COOKIE => $cookie->getValue()]);
        $this->assertTrue($twoFactor->isTrustedDevice($request, $user->fresh()));

        // Resetting 2FA forgets trusted devices.
        $twoFactor->reset($user);
        $this->assertFalse($twoFactor->isTrustedDevice($request, $user->fresh()));
    }

    public function test_people_can_set_up_an_authenticator_app_from_their_profile(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(TwoFactorSettings::class)
            ->call('ask', 'startAppSetup')
            ->set('password', 'wrong')
            ->call('confirmPassword')
            ->assertHasErrors('password')
            ->set('password', 'password')
            ->call('confirmPassword')
            ->assertSet('settingUpApp', true);

        $secret = session('two_factor.setup_secret');
        $component->set('code', (new Google2FA)->getCurrentOtp($secret))->call('confirmApp')->assertHasNoErrors();

        $user->refresh();
        $this->assertSame(TwoFactorMethod::App, $user->twoFactorMethod());
        $this->assertCount(8, $user->two_factor_recovery_codes);
        $this->assertNotSame($secret, $user->getRawOriginal('two_factor_secret'), 'The secret is stored encrypted');
    }

    public function test_two_factor_cannot_be_turned_off_when_required(): void
    {
        settings()->set(['two_factor_required' => true]);
        $user = User::factory()->create(['two_factor_method' => TwoFactorMethod::Email]);

        Livewire::actingAs($user)->test(TwoFactorSettings::class)
            ->call('ask', 'disable')
            ->set('password', 'password')
            ->call('confirmPassword')
            ->assertForbidden();
    }

    public function test_admins_can_reset_someones_two_factor(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['two_factor_method' => TwoFactorMethod::App, 'two_factor_secret' => 'x', 'two_factor_confirmed_at' => now()]);

        Livewire::actingAs($admin)->test(StaffForm::class)->call('edit', $user->id)->call('resetTwoFactor');

        $this->assertNull($user->fresh()->two_factor_method);
    }

    public function test_the_server_command_can_lift_the_requirement(): void
    {
        settings()->set(['two_factor_required' => true]);

        $this->artisan('app:reset-two-factor', ['--disable-requirement' => true])->assertSuccessful();

        $this->assertFalse(settings('two_factor_required'));
    }

    public function test_admins_can_require_two_factor_from_settings(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(Settings::class, ['tab' => 'security'])
            ->set('two_factor_required', true)
            ->call('saveSecurity')
            ->assertHasNoErrors();

        $this->assertTrue(settings('two_factor_required'));
        // The admin who turned it on isn't locked out of the current session.
        $this->actingAs($admin)->withSession([TwoFactor::PASSED => true])->get('/settings')->assertOk();
    }
}
