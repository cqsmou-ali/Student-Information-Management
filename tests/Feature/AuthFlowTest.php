<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_log_in_and_open_admin_dashboard(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'Admin@12345'])->assertRedirect('/dashboard');
        $this->get('/admin')->assertOk();
    }

    public function test_wrong_password_fails_with_generic_message(): void
    {
        $this->post('/login', ['username' => 'admin', 'password' => 'bad'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_account_locks_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'registrar1', 'password' => 'bad']);
        }
        $this->post('/login', ['username' => 'registrar1', 'password' => 'Staff@12345'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_student_must_change_temporary_password_first(): void
    {
        $this->post('/login', ['username' => '2023-0001', 'password' => 'Student@12345']);
        $this->get('/student')->assertRedirect('/change-password');

        $this->put('/change-password', [
            'current_password' => 'Student@12345',
            'password' => 'NewPass123',
            'password_confirmation' => 'NewPass123',
        ])->assertRedirect('/dashboard');

        $this->get('/student')->assertOk();
    }

    public function test_student_cannot_open_admin_area(): void
    {
        $user = User::where('username', '2023-0001')->first();
        $user->forceFill(['must_change_password' => false])->save();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->assertDatabaseHas('activity_logs', ['action' => 'access_denied', 'user_id' => $user->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }
}
