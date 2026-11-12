<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->staff = User::where('username', 'registrar1')->first();
    }

    private function payload(array $over = []): array
    {
        $section = Section::first();

        return array_merge([
            'student_number' => '2026-0100', 'first_name' => 'Liza', 'middle_name' => 'Cruz', 'last_name' => "D'Souza",
            'birthdate' => '2006-02-14', 'sex' => 'Female', 'email' => 'liza.dsouza@student.cvsu.edu.ph',
            'contact_number' => '0917 123 4567', 'address' => 'Indang, Cavite', 'emergency_contact' => 'Mother - 0918 000 1111',
            'course_id' => $section->course_id, 'year_level_id' => $section->year_level_id, 'section_id' => $section->id,
            'enrollment_status' => 'Enrolled', 'date_enrolled' => '2026-08-10',
        ], $over);
    }

    public function test_pages_render_for_staff(): void
    {
        $student = Student::first();
        foreach (['/staff', '/staff/students', '/staff/students/create', "/staff/students/{$student->id}", "/staff/students/{$student->id}/edit",
                  "/staff/students/{$student->id}/enrollment", '/staff/reports', '/staff/academics', '/profile'] as $url) {
            $this->actingAs($this->staff)->get($url)->assertOk();
        }
    }

    public function test_only_staff_can_use_registrar_pages(): void
    {
        $this->get('/staff/students')->assertRedirect('/login');

        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin)->get('/staff/students')->assertForbidden();

        $student = User::where('username', '2023-0001')->first();
        $student->forceFill(['must_change_password' => false])->save();
        $this->actingAs($student)->get('/staff/students')->assertForbidden();
        $this->actingAs($student)->post('/staff/students', $this->payload())->assertForbidden();
        $this->actingAs($student)->get('/profile')->assertOk()->assertSee('Student record');
    }

    public function test_staff_cannot_delete_students(): void
    {
        $student = Student::first();
        $this->actingAs($this->staff)->delete("/staff/students/{$student->id}")->assertStatus(405);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_register_student_creates_account_with_temporary_password(): void
    {
        $response = $this->actingAs($this->staff)->post('/staff/students', $this->payload());
        $student = Student::where('student_number', '2026-0100')->firstOrFail();
        $response->assertRedirect("/staff/students/{$student->id}");

        $temp = session('temp_password');
        $this->assertNotEmpty($temp);
        $user = $student->user;
        $this->assertSame('student', $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertSame('2026-0100', $user->username);
        $this->assertNotSame($temp, $user->password_hash); // stored hashed
        $this->assertDatabaseHas('activity_logs', ['action' => 'student_registered', 'user_id' => $this->staff->id]);

        // The student can log in with it, and is forced to change it.
        auth()->logout();
        $this->post('/login', ['username' => '2026-0100', 'password' => $temp])->assertRedirect('/dashboard');
        $this->get('/student')->assertRedirect('/change-password');
    }

    public function test_registration_validates_input(): void
    {
        $this->actingAs($this->staff)->post('/staff/students', $this->payload(['student_number' => '2023-0001']))->assertSessionHasErrors('student_number');
        $this->actingAs($this->staff)->post('/staff/students', $this->payload(['email' => 'admin@cvsu.edu.ph']))->assertSessionHasErrors('email');
        $this->actingAs($this->staff)->post('/staff/students', $this->payload(['first_name' => '<script>x</script>']))->assertSessionHasErrors('first_name');
        $this->actingAs($this->staff)->post('/staff/students', $this->payload(['birthdate' => '2999-01-01']))->assertSessionHasErrors('birthdate');
        $this->actingAs($this->staff)->post('/staff/students', $this->payload(['sex' => 'Other']))->assertSessionHasErrors('sex');
        $this->actingAs($this->staff)->post('/staff/students', $this->payload(['student_number' => "1'; DROP TABLE users;--"]))->assertSessionHasErrors('student_number');

        $other = Section::where('course_id', '!=', Section::first()->course_id)->first();
        $this->actingAs($this->staff)->post('/staff/students', $this->payload(['section_id' => $other->id]))->assertSessionHasErrors('section_id');
        $this->assertDatabaseMissing('students', ['student_number' => '2026-0100']);
    }

    public function test_update_profile_syncs_login_email_and_logs(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $data = $this->payload(['email' => 'juan.new@student.cvsu.edu.ph', 'first_name' => 'Juan Carlo']);
        unset($data['student_number']);

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $data)->assertRedirect("/staff/students/{$student->id}");

        $this->assertSame('juan.new@student.cvsu.edu.ph', $student->fresh()->email);
        $this->assertSame('juan.new@student.cvsu.edu.ph', $student->user->fresh()->email);
        $this->assertDatabaseHas('activity_logs', ['action' => 'student_updated']);
    }

    public function test_update_cannot_steal_another_users_email(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $data = $this->payload(['email' => 'admin@cvsu.edu.ph']);
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $data)->assertSessionHasErrors('email');
    }

    public function test_assign_course_and_section(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $target = Section::where('course_id', '!=', $student->course_id)->first();

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", [
            'course_id' => $target->course_id, 'year_level_id' => $target->year_level_id, 'section_id' => $target->id,
            'enrollment_status' => 'LOA', 'date_enrolled' => '2023-08-14',
        ])->assertRedirect("/staff/students/{$student->id}");

        $fresh = $student->fresh();
        $this->assertSame($target->id, $fresh->section_id);
        $this->assertSame('LOA', $fresh->enrollment_status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'enrollment_updated']);

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", [
            'course_id' => $target->course_id, 'year_level_id' => $target->year_level_id, 'section_id' => Section::where('course_id', '!=', $target->course_id)->first()->id,
            'enrollment_status' => 'Enrolled', 'date_enrolled' => '2023-08-14',
        ])->assertSessionHasErrors('section_id');
    }

    public function test_search_and_filters(): void
    {
        $this->actingAs($this->staff)->get('/staff/students?q=Maria')->assertSee('2023-0002')->assertDontSee('2023-0001');
        $this->actingAs($this->staff)->get('/staff/students?q=2024-0003')->assertSee('Reyes')->assertDontSee('Santos, Maria');
        $bscs = \App\Models\Course::where('course_code', 'BSCS')->first();
        $this->actingAs($this->staff)->get('/staff/students?course_id=' . $bscs->id)->assertSee('2023-0002')->assertDontSee('2022-0005');
        // wildcard characters are treated literally, and injection text just matches nothing
        $this->actingAs($this->staff)->get('/staff/students?q=%25')->assertSee('No students match');
        $this->actingAs($this->staff)->get("/staff/students?q=' OR 1=1 --")->assertOk()->assertSee('No students match');
    }

    public function test_hidden_students_do_not_appear_or_open(): void
    {
        $student = Student::where('student_number', '2022-0005')->first();
        $student->forceFill(['is_deleted' => true])->save();
        $this->actingAs($this->staff)->get('/staff/students')->assertDontSee('2022-0005');
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}")->assertNotFound();
    }

    public function test_report_and_csv_export(): void
    {
        $this->actingAs($this->staff)->get('/staff/reports?status=Enrolled')->assertOk()->assertSee('5</strong> student(s)', false);

        Student::where('student_number', '2023-0001')->first()->forceFill(['last_name' => '=HYPERLINK("x")'])->save();
        $response = $this->actingAs($this->staff)->get('/staff/students/export');
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Student No.', $csv);
        $this->assertStringContainsString('2024-0004', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv); // formula neutralised
        $this->assertDatabaseHas('activity_logs', ['action' => 'report_exported']);
    }

    public function test_dashboard_shows_student_total(): void
    {
        $this->actingAs($this->staff)->get('/staff')->assertOk()->assertSee('Registered students')->assertSee('Register student');
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Registered students');
    }
}
