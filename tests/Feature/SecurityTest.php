<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Setup;
use App\Livewire\Profile;
use App\Livewire\TaskForm;
use App\Livewire\Tasks\Show;
use App\Models\Plan;
use App\Models\Project;
use App\Models\StaffGroup;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Support\SetupToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_parameters_cannot_inject_javascript(): void
    {
        $manager = User::factory()->manager()->create();
        $payload = "'}),alert(document.cookie),({a:'";

        $html = $this->actingAs($manager)->get('/plans?period='.urlencode($payload))->assertOk()->getContent();

        $this->assertStringNotContainsString('alert(document.cookie)', $html);
        $this->assertStringContainsString('period: $wire.period', $html);

        $this->actingAs($manager)->get('/board?view='.urlencode($payload).'&priority='.urlencode($payload))
            ->assertOk()
            ->assertDontSee('alert(document.cookie)', false);
    }

    public function test_staff_cannot_open_projects_they_are_not_part_of(): void
    {
        $staff = User::factory()->create();
        $secret = Project::create(['name' => 'Secret merger']);
        $mine = Project::create(['name' => 'My project']);
        Task::create(['title' => 'My work', 'project_id' => $mine->id, 'assignee_id' => $staff->id]);

        $this->actingAs($staff)->get('/projects/'.$secret->id)->assertForbidden();
        $this->actingAs($staff)->get('/projects/'.$mine->id)->assertOk();
        $this->actingAs($staff)->get('/projects')->assertSee('My project')->assertDontSee('Secret merger');
    }

    public function test_attachments_are_only_downloadable_by_people_who_can_see_them(): void
    {
        Storage::fake('local');
        $manager = User::factory()->manager()->create();
        $staff = User::factory()->create();

        $project = Project::create(['name' => 'Confidential']);
        $projectFile = $project->attach(UploadedFile::fake()->create('contract.pdf', 10, 'application/pdf'), $manager);

        $task = Task::create(['title' => 'Someone else', 'assignee_id' => $manager->id]);
        $taskFile = $task->attach(UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'), $manager);

        $this->actingAs($staff)->get('/attachments/'.$projectFile->id)->assertForbidden();
        $this->actingAs($staff)->get('/attachments/'.$taskFile->id)->assertForbidden();
        $this->actingAs($manager)->get('/attachments/'.$projectFile->id)->assertOk();
    }

    public function test_staff_only_see_plans_meant_for_them(): void
    {
        $group = StaffGroup::create(['name' => 'Sales']);
        $staff = User::factory()->create(['staff_group_id' => $group->id]);
        $other = User::factory()->create();
        $dates = ['period' => 'monthly', 'starts_on' => today()->startOfMonth(), 'ends_on' => today()->endOfMonth()];

        $everyone = Plan::create(['title' => 'Company goal'] + $dates);
        $team = Plan::create(['title' => 'Sales goal', 'staff_group_id' => $group->id] + $dates);
        $private = Plan::create(['title' => 'Someone else personal goal', 'user_id' => $other->id] + $dates);

        $this->actingAs($staff)->get('/plans')->assertSee('Company goal')->assertSee('Sales goal')->assertDontSee('Someone else personal goal');
        $this->actingAs($staff)->get('/plans/'.$private->id)->assertForbidden();
        $this->actingAs($staff)->get('/plans/'.$team->id)->assertOk();
        $this->actingAs($staff)->get('/plans/'.$everyone->id)->assertOk();
    }

    public function test_staff_cannot_file_tasks_under_projects_they_cannot_see(): void
    {
        $staff = User::factory()->create();
        $secret = Project::create(['name' => 'Secret']);

        Livewire::actingAs($staff)->test(TaskForm::class)
            ->call('create')
            ->set('title', 'Sneaky')
            ->set('reminder', '')
            ->set('project_id', $secret->id)
            ->call('save')
            ->assertHasErrors('project_id');
    }

    public function test_task_pages_recheck_access_on_every_request(): void
    {
        $staff = User::factory()->create();
        $task = Task::create(['title' => 'Was mine', 'assignee_id' => $staff->id]);

        $component = Livewire::actingAs($staff)->test(Show::class, ['task' => $task]);
        $task->update(['assignee_id' => User::factory()->create()->id]);

        $component->call('refresh')->assertForbidden();
    }

    public function test_setup_requires_a_generated_token_in_production(): void
    {
        $this->app['env'] = 'production';
        config(['app.setup_token' => null]);

        $this->assertTrue(SetupToken::required());
        $token = SetupToken::value();
        $this->assertSame(40, strlen($token));

        $form = fn () => Livewire::test(Setup::class)
            ->set('app_name', 'Acme')
            ->set('name', 'Admin')
            ->set('email', 'admin@acme.test')
            ->set('password', 'a-strong-passw0rd')
            ->set('password_confirmation', 'a-strong-passw0rd');

        $form()->set('setup_token', 'guess')->call('save')->assertHasErrors('setup_token');
        $this->assertSame(0, User::count());

        $form()->set('setup_token', $token)->call('save')->assertRedirect(route('dashboard'));
        $this->assertSame(1, User::count());
        $this->assertFalse(File::exists(storage_path('app/private/setup-token')), 'The used token should be removed');
    }

    public function test_setup_token_guessing_is_rate_limited(): void
    {
        config(['app.setup_token' => 'the-real-token']);

        for ($i = 0; $i < 6; $i++) {
            $component = Livewire::test(Setup::class)
                ->set('app_name', 'Acme')->set('name', 'A')->set('email', 'a@acme.test')
                ->set('password', 'a-strong-passw0rd')->set('password_confirmation', 'a-strong-passw0rd')
                ->set('setup_token', 'wrong-'.$i)
                ->call('save');
        }

        $this->assertStringContainsString('Too many attempts', $component->errors()->first('setup_token'));
    }

    public function test_login_is_rate_limited_per_ip_across_accounts(): void
    {
        User::factory()->admin()->create();

        for ($i = 0; $i < 21; $i++) {
            $component = Livewire::test(Login::class)
                ->set('email', "nobody{$i}@example.com")
                ->set('password', 'wrong-password')
                ->call('login');
        }

        $this->assertStringContainsString('Too many attempts', $component->errors()->first('email'));
    }

    public function test_pages_send_security_headers(): void
    {
        User::factory()->admin()->create();

        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_task_titles_cannot_inject_links_into_emails(): void
    {
        $staff = User::factory()->create();
        $task = Task::create(['title' => '[Reset your password](https://evil.test)']);

        $html = (string) (new TaskAssigned($task))->toMail($staff)->render();

        $this->assertStringNotContainsString('href="https://evil.test"', $html);
    }

    public function test_passwords_need_ten_characters_with_letters_and_numbers(): void
    {
        $this->assertTrue(validator(['p' => 'short1'], ['p' => Password::defaults()])->fails());
        $this->assertTrue(validator(['p' => 'onlyletterslong'], ['p' => Password::defaults()])->fails());
        $this->assertFalse(validator(['p' => 'letters-and-123'], ['p' => Password::defaults()])->fails());
    }

    public function test_changing_email_needs_the_current_password(): void
    {
        $user = User::factory()->create(['email' => 'me@company.test']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('email', 'attacker@evil.test')
            ->call('saveProfile')
            ->assertHasErrors('email_password');
        $this->assertSame('me@company.test', $user->fresh()->email);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('email', 'new@company.test')
            ->set('email_password', 'password')
            ->call('saveProfile')
            ->assertHasNoErrors();
        $this->assertSame('new@company.test', $user->fresh()->email);
    }
}
