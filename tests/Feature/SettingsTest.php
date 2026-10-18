<?php

namespace Tests\Feature;

use App\Livewire\Settings;
use App\Models\Setting;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_rebrand_the_app(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(Settings::class)
            ->set('app_name', 'Foxy Tasks')
            ->set('primary_color', '#0f766e')
            ->set('logo', UploadedFile::fake()->image('logo.png', 120, 120))
            ->call('saveBranding')
            ->assertHasNoErrors();

        $this->assertSame('Foxy Tasks', settings('app_name'));
        $this->assertSame('#0f766e', settings('primary_color'));
        Storage::disk('local')->assertExists(settings('logo_path'));

        $this->actingAs($admin)->get('/')->assertSee('Foxy Tasks')->assertSee('--brand: #0f766e', false);
        $this->get(settings()->logoUrl())->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_smtp_settings_are_saved_encrypted_and_applied(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(Settings::class)
            ->set('mail_enabled', true)
            ->set('mail_host', 'smtp.mailprovider.test')
            ->set('mail_port', '465')
            ->set('mail_username', 'apikey')
            ->set('mail_password', 's3cret')
            ->set('mail_encryption', 'ssl')
            ->set('mail_from_address', 'tasks@company.test')
            ->call('saveMail')
            ->assertHasNoErrors();

        $this->assertStringNotContainsString('s3cret', Setting::find('mail_password')->value);
        $this->assertSame('s3cret', settings('mail_password'));

        Mailer::configure(settings());
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.mailprovider.test', config('mail.mailers.smtp.host'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('tasks@company.test', config('mail.from.address'));
    }

    public function test_only_admins_can_change_settings(): void
    {
        Livewire::actingAs(User::factory()->manager()->create())
            ->test(Settings::class)
            ->assertForbidden();
    }
}
