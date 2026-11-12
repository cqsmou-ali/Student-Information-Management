<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Forgot password?');
        $this->get('/forgot-password')->assertOk()->assertSee('Email reset link');
        $this->get('/reset-password/sometoken?email=a@b.c')->assertOk()->assertSee('Reset password');
    }

    public function test_reset_link_is_emailed_to_active_account(): void
    {
        Notification::fake();
        $user = User::where('username', 'registrar1')->first();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_same_message_and_no_mail(): void
    {
        Notification::fake();
        $known = $this->post('/forgot-password', ['email' => 'registrar1@cvsu.edu.ph'])->getSession()->get('status');
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com'])->getSession()->get('status');

        $this->assertSame($known, $unknown);
        Notification::assertCount(1);
    }

    public function test_deactivated_account_gets_no_link(): void
    {
        Notification::fake();
        $user = User::where('username', 'registrar2')->first();
        $user->forceFill(['is_active' => false])->save();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_full_reset_flow_changes_password_and_unlocks(): void
    {
        $user = User::where('username', '2023-0001')->first();
        $user->forceFill(['locked_until' => now()->addMinutes(10)])->save();
        $token = null;

        Notification::fake();
        $this->post('/forgot-password', ['email' => $user->email]);
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;
            return true;
        });

        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'Brand1NewPass', 'password_confirmation' => 'Brand1NewPass',
        ])->assertRedirect('/login');

        $user->refresh();
        $this->assertTrue(Hash::check('Brand1NewPass', $user->password_hash));
        $this->assertFalse($user->must_change_password);
        $this->assertNull($user->locked_until);

        $this->post('/login', ['username' => '2023-0001', 'password' => 'Brand1NewPass'])->assertRedirect('/dashboard');
        $this->get('/student')->assertOk();
    }

    public function test_bad_token_and_weak_password_are_rejected(): void
    {
        $user = User::where('username', 'admin')->first();

        $this->post('/reset-password', [
            'token' => 'bad', 'email' => $user->email,
            'password' => 'Brand1NewPass', 'password_confirmation' => 'Brand1NewPass',
        ])->assertSessionHasErrors('email');

        $this->post('/reset-password', [
            'token' => 'bad', 'email' => $user->email,
            'password' => 'weak', 'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');
    }

    public function test_signed_in_user_is_sent_to_dashboard_from_login_page(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@12345']);
        $this->get('/login')->assertRedirect('/dashboard');
    }

    public function test_remember_me_checkbox_logs_in(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@12345', 'remember' => '1'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_dashboards_render_in_new_layout(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@12345']);
        $this->get('/admin')->assertOk()->assertSee('Administrator dashboard')->assertSee('CEIT SIS');
    }
}
