<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
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

    /** The hidden field an edit form carries: the record's version when the form was opened. */
    private function version(Student $student): array
    {
        return ['loaded_at' => $student->fresh()->editVersion()];
    }

    public function test_pages_render_for_staff(): void
    {
        $student = Student::first();
        foreach (['/staff', '/staff/students', '/staff/students/create', "/staff/students/{$student->id}", "/staff/students/{$student->id}/edit",
                  "/staff/students/{$student->id}/enrollment", '/staff/reports', '/staff/academics', '/profile',
                  "/staff/students/{$student->id}/print", '/staff/reports/summary'] as $url) {
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

        // The session only holds the encrypted value; the page shows the real password once, uncached.
        $sealed = session('temp_password');
        $temp = Crypt::decryptString($sealed);
        $this->assertMatchesRegularExpression('/^Cv[a-z0-9]{5}\d{4}$/', $temp);
        $this->assertStringNotContainsString($temp, $sealed);
        $page = $this->actingAs($this->staff)->get("/staff/students/{$student->id}");
        $page->assertSee($temp)->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}")->assertDontSee($temp); // shown only once

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

    public function test_staff_profile_shows_staff_details(): void
    {
        $this->actingAs($this->staff)->get('/profile')->assertOk()
            ->assertSee('Staff details')->assertSee('Rosa M. Villanueva')->assertSee('Registrar Staff')
            ->assertSee('CEIT Office of the College Registrar');

        // A staff account without details yet still renders.
        $blank = User::where('username', 'registrar2')->first();
        $blank->forceFill(['full_name' => null, 'position' => null, 'office' => null])->save();
        $this->actingAs($blank)->get('/profile')->assertOk()->assertSee('Not set');
    }

    public function test_staff_details_card_is_for_staff_only(): void
    {
        $this->actingAs(User::where('username', 'admin')->first())->get('/profile')->assertOk()->assertDontSee('Staff details');

        $student = User::where('username', '2023-0001')->first();
        $student->forceFill(['must_change_password' => false])->save();
        $this->actingAs($student)->get('/profile')->assertOk()->assertDontSee('Staff details')->assertSee('Student record');
    }

    public function test_tampered_temporary_password_is_not_shown(): void
    {
        $student = Student::first();
        $this->actingAs($this->staff)->withSession(['temp_password' => 'not-encrypted'])
            ->get("/staff/students/{$student->id}")->assertOk()->assertDontSee('Give this temporary password');
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
        $data = $this->payload(['email' => 'juan.new@student.cvsu.edu.ph', 'first_name' => 'Juan Carlo', 'change_reason' => 'Name correction per PSA certificate']);
        unset($data['student_number']);

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $data + $this->version($student))->assertRedirect("/staff/students/{$student->id}");

        $this->assertSame('juan.new@student.cvsu.edu.ph', $student->fresh()->email);
        $this->assertSame('juan.new@student.cvsu.edu.ph', $student->user->fresh()->email);
        $this->assertDatabaseHas('activity_logs', ['action' => 'student_updated']);
    }

    public function test_update_cannot_steal_another_users_email(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $data = $this->payload(['email' => 'admin@cvsu.edu.ph']);
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $data + $this->version($student))->assertSessionHasErrors('email');
    }

    public function test_assign_course_and_section(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $target = Section::where('course_id', '!=', $student->course_id)->first();

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", [
            'course_id' => $target->course_id, 'year_level_id' => $target->year_level_id, 'section_id' => $target->id,
            'enrollment_status' => 'LOA', 'date_enrolled' => '2023-08-14',
        ] + $this->version($student))->assertRedirect("/staff/students/{$student->id}");

        $fresh = $student->fresh();
        $this->assertSame($target->id, $fresh->section_id);
        $this->assertSame('LOA', $fresh->enrollment_status);
        $this->assertDatabaseHas('activity_logs', ['action' => 'enrollment_updated']);

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", [
            'course_id' => $target->course_id, 'year_level_id' => $target->year_level_id, 'section_id' => Section::where('course_id', '!=', $target->course_id)->first()->id,
            'enrollment_status' => 'Enrolled', 'date_enrolled' => '2023-08-14',
        ] + $this->version($student))->assertSessionHasErrors('section_id');
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

    public function test_protected_columns_cannot_be_mass_assigned(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $other = User::where('username', 'admin')->first();

        // Model level: even a careless $request->all() cannot reach these columns.
        $student->fill(['is_deleted' => true, 'user_id' => $other->id, 'id' => 999])->save();
        $fresh = $student->fresh();
        $this->assertFalse($fresh->is_deleted);
        $this->assertNotSame($other->id, $fresh->user_id);

        // Request level: extra fields on the profile and enrollment forms are ignored.
        $data = $this->payload(['email' => $student->email, 'is_deleted' => 1, 'user_id' => $other->id, 'student_number' => '9999-9999', 'change_reason' => 'Record correction']);
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $data + $this->version($student))->assertSessionHasNoErrors();
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", $data + $this->version($student))->assertSessionHasNoErrors();
        $fresh = $student->fresh();
        $this->assertFalse($fresh->is_deleted);
        $this->assertSame('2023-0001', $fresh->student_number);
        $this->assertNotSame($other->id, $fresh->user_id);
    }

    public function test_profile_update_logs_old_and_new_values(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $data = $student->only(['first_name', 'middle_name', 'last_name', 'sex', 'email', 'contact_number', 'address', 'emergency_contact'])
            + ['birthdate' => '2004-03-16'];
        $data['first_name'] = 'Juan Carlo';
        $data['change_reason'] = 'Name correction per PSA certificate';

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $data + $this->version($student))->assertSessionHasNoErrors();

        $log = ActivityLog::where('action', 'student_updated')->latest('id')->first();
        $this->assertSame($this->staff->id, $log->user_id);
        $this->assertSame(Student::class, $log->subject_type);
        $this->assertSame($student->id, $log->subject_id);
        $this->assertSame([
            'first_name' => ['from' => 'Juan', 'to' => 'Juan Carlo'],
            'birthdate'  => ['from' => '2004-03-15', 'to' => '2004-03-16'],
            'change_reason' => 'Name correction per PSA certificate',
        ], $log->field_changes); // unchanged fields are not listed
        $this->assertStringContainsString('Reason: Name correction per PSA certificate', $log->details);
    }

    /** The current profile of 2023-0001 as the edit form would send it, with some fields changed. */
    private function profileForm(Student $student, array $over = []): array
    {
        return array_merge($student->only(['first_name', 'middle_name', 'last_name', 'sex', 'email', 'contact_number', 'address', 'emergency_contact']),
            ['birthdate' => $student->birthdate->format('Y-m-d')], $over, $this->version($student));
    }

    public function test_identity_changes_need_a_reason(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();

        foreach ([['last_name' => 'Dela Paz'], ['middle_name' => null], ['first_name' => 'juan'], ['birthdate' => '2004-03-14'], ['sex' => 'Female']] as $change) {
            $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $this->profileForm($student, $change))
                ->assertSessionHasErrors('change_reason');
        }
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $this->profileForm($student, ['last_name' => 'Dela Paz', 'change_reason' => '   ']))
            ->assertSessionHasErrors('change_reason'); // blank after trimming

        $fresh = $student->fresh();
        $this->assertSame(['Juan', 'Santos', 'Dela Cruz', 'Male', '2004-03-15'],
            [$fresh->first_name, $fresh->middle_name, $fresh->last_name, $fresh->sex, $fresh->birthdate->format('Y-m-d')]);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'student_updated']);
    }

    public function test_reason_is_validated_and_logged(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $this->profileForm($student, ['last_name' => 'Dela Paz', 'change_reason' => 'abc']))
            ->assertSessionHasErrors('change_reason'); // too short
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $this->profileForm($student, ['last_name' => 'Dela Paz', 'change_reason' => str_repeat('x', 256)]))
            ->assertSessionHasErrors('change_reason'); // too long

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $this->profileForm($student, ['last_name' => 'Dela Paz', 'change_reason' => 'Married name, marriage certificate on file']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Dela Paz', $student->fresh()->last_name);
        $log = $student->activityLogs()->where('action', 'student_updated')->firstOrFail();
        $this->assertSame(['from' => 'Dela Cruz', 'to' => 'Dela Paz'], $log->field_changes['last_name']);
        $this->assertSame('Married name, marriage certificate on file', $log->field_changes['change_reason']);
        $this->assertArrayNotHasKey('change_reason', $student->fresh()->getAttributes()); // not stored on the student
    }

    public function test_contact_changes_need_no_reason(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $this->profileForm($student, [
            'contact_number' => '0999 000 1234', 'address' => 'Trece Martires, Cavite', 'emergency_contact' => 'Father - 0918 222 3333',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('0999 000 1234', $student->fresh()->contact_number);
        $log = $student->activityLogs()->where('action', 'student_updated')->firstOrFail();
        $this->assertArrayNotHasKey('change_reason', $log->field_changes);
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}/edit")->assertSee('Reason for change');
        $this->actingAs($this->staff)->get('/staff/students/create')->assertDontSee('Reason for change');
    }

    public function test_enrollment_update_logs_old_and_new_values(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $oldSection = $student->section_id;
        $target = Section::where('course_id', $student->course_id)->where('year_level_id', '!=', $student->year_level_id)->first();

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", [
            'course_id' => $target->course_id, 'year_level_id' => $target->year_level_id, 'section_id' => $target->id,
            'enrollment_status' => 'LOA', 'date_enrolled' => '2023-08-14',
        ] + $this->version($student))->assertSessionHasNoErrors();

        $log = $student->activityLogs()->where('action', 'enrollment_updated')->firstOrFail();
        $this->assertEquals(['from' => $oldSection, 'to' => $target->id], $log->field_changes['section_id']);
        $this->assertSame(['from' => 'Enrolled', 'to' => 'LOA'], $log->field_changes['enrollment_status']);
        $this->assertArrayNotHasKey('course_id', $log->field_changes);
    }

    public function test_viewing_and_registering_are_logged_against_the_student(): void
    {
        $student = Student::first();
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}")->assertOk();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'student_viewed', 'user_id' => $this->staff->id, 'subject_type' => Student::class, 'subject_id' => $student->id,
        ]);

        $this->actingAs($this->staff)->post('/staff/students', $this->payload());
        $new = Student::where('student_number', '2026-0100')->firstOrFail();
        $this->assertSame(['student_registered'], $new->activityLogs()->pluck('action')->all());
    }

    public function test_activity_logs_cannot_be_changed_or_deleted(): void
    {
        ActivityLog::record('student_updated', 'original', $this->staff->id, 'staff');
        $log = ActivityLog::latest('id')->first();

        $this->assertFalse($log->update(['details' => 'tampered']));
        $this->assertFalse($log->delete());
        $this->assertDatabaseHas('activity_logs', ['id' => $log->id, 'details' => 'original']);
    }

    public function test_edit_forms_carry_the_record_version(): void
    {
        $student = Student::first();
        $hidden = 'name="loaded_at" value="' . $student->editVersion() . '"';
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}/edit")->assertSee($hidden, false);
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}/enrollment")->assertSee($hidden, false);
    }

    public function test_stale_profile_save_is_refused_and_keeps_input(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $opened = $this->version($student);                       // registrar 1 opens the form

        $this->travel(5)->seconds();
        $student->fresh()->update(['contact_number' => '0999 111 2222']); // registrar 2 saves first

        $data = $student->only(['first_name', 'middle_name', 'last_name', 'sex', 'email', 'contact_number', 'address', 'emergency_contact'])
            + ['birthdate' => '2004-03-15'];
        $data['first_name'] = 'Overwrite';
        $data['change_reason'] = 'Typo in first name';
        $this->actingAs($this->staff)->from("/staff/students/{$student->id}/edit")
            ->put("/staff/students/{$student->id}", $data + $opened)
            ->assertRedirect("/staff/students/{$student->id}/edit")
            ->assertSessionHasErrors('loaded_at')
            ->assertSessionHasInput('first_name', 'Overwrite');

        $fresh = $student->fresh();
        $this->assertSame('Juan', $fresh->first_name);              // nothing from the stale form was saved
        $this->assertSame('0999 111 2222', $fresh->contact_number); // registrar 2's change survives
        $this->assertDatabaseMissing('activity_logs', ['action' => 'student_updated']);

        // The refused form shows the warning, and the reloaded form carries the new version.
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}/edit")->assertSee('Another user changed this record');
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}", $data + $this->version($student))->assertSessionHasNoErrors();
        $this->assertSame('Overwrite', $student->fresh()->first_name);
    }

    public function test_stale_or_missing_version_blocks_enrollment_save(): void
    {
        $student = Student::where('student_number', '2023-0001')->first();
        $data = ['course_id' => $student->course_id, 'year_level_id' => $student->year_level_id, 'section_id' => $student->section_id,
                 'enrollment_status' => 'Dropped', 'date_enrolled' => '2023-08-14'];
        $opened = $this->version($student);

        $this->travel(5)->seconds();
        $student->fresh()->update(['enrollment_status' => 'LOA']);

        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", $data + $opened)->assertSessionHasErrors('loaded_at');
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", $data)->assertSessionHasErrors('loaded_at');
        $this->actingAs($this->staff)->put("/staff/students/{$student->id}/enrollment", $data + ['loaded_at' => ['x']])->assertSessionHasErrors('loaded_at');
        $this->assertSame('LOA', $student->fresh()->enrollment_status);
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

    public function test_student_information_sheet(): void
    {
        $student = Student::where('student_number', '2023-0001')->first(); // has a seeded grade (ITEC 50)

        $this->actingAs($this->staff)->get("/staff/students/{$student->id}")->assertSee("/staff/students/{$student->id}/print", false);
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}/print")->assertOk()
            ->assertSee('Student Information Sheet')->assertSee('2023-0001')->assertSee($student->fullName())
            ->assertSee('Bachelor of Science in Information Technology')->assertSee('2-A')
            ->assertSee('Prepared by')->assertSee('Rosa M. Villanueva')
            ->assertDontSee('ITEC 50'); // grades are out of scope

        $log = $student->activityLogs()->where('action', 'report_generated')->firstOrFail();
        $this->assertSame($this->staff->id, $log->user_id);
    }

    public function test_student_sheet_access_rules(): void
    {
        $student = Student::where('student_number', '2022-0005')->first();

        $this->get("/staff/students/{$student->id}/print")->assertRedirect('/login');
        $this->actingAs(User::where('username', 'admin')->first())->get("/staff/students/{$student->id}/print")->assertForbidden();
        $learner = User::where('username', '2023-0001')->first();
        $learner->forceFill(['must_change_password' => false])->save();
        $this->actingAs($learner)->get("/staff/students/{$student->id}/print")->assertForbidden();
        $this->actingAs($learner)->get('/staff/reports/summary')->assertForbidden();

        $student->forceFill(['is_deleted' => true])->save();
        $this->actingAs($this->staff)->get("/staff/students/{$student->id}/print")->assertNotFound();
    }

    public function test_summary_counts_by_course_year_and_section(): void
    {
        $response = $this->actingAs($this->staff)->get('/staff/reports/summary')->assertOk()->assertSee('5</strong> student(s)', false);
        $this->assertSame(5, $response->viewData('grandTotal'));
        $bsit = $response->viewData('report')->firstWhere('code', 'BSIT');
        $this->assertSame(2, $bsit['total']);
        $this->assertSame(['1st Year', '2nd Year'], $bsit['years']->pluck('name')->all()); // years with no sections and no students are left out
        $this->assertSame([['name' => '1-A', 'total' => 1], ['name' => '1-B', 'total' => 0]], $bsit['years'][0]['sections']->all());
        $this->assertDatabaseHas('activity_logs', ['action' => 'report_generated', 'user_id' => $this->staff->id]);

        // A student without a section is counted on its own row; hidden students are not counted.
        Student::where('student_number', '2024-0003')->first()->forceFill(['section_id' => null])->save();
        Student::where('student_number', '2022-0005')->first()->forceFill(['is_deleted' => true])->save();
        $response = $this->actingAs($this->staff)->get('/staff/reports/summary')->assertSee('No section yet');
        $this->assertSame(4, $response->viewData('grandTotal'));
        $bsit = $response->viewData('report')->firstWhere('code', 'BSIT');
        $this->assertSame(1, $bsit['years'][0]['unassigned']);
        $this->assertSame(0, $bsit['years'][0]['sections'][0]['total']);
        $this->assertSame(1, $response->viewData('report')->firstWhere('code', 'BSCpE')['total']);
    }

    public function test_summary_status_filter_is_validated(): void
    {
        Student::where('student_number', '2023-0002')->first()->forceFill(['enrollment_status' => 'LOA'])->save();
        $response = $this->actingAs($this->staff)->get('/staff/reports/summary?status=LOA')->assertOk();
        $this->assertSame(1, $response->viewData('grandTotal'));
        $this->assertSame(1, $response->viewData('report')->firstWhere('code', 'BSCS')['total']);

        $this->actingAs($this->staff)->get("/staff/reports/summary?status=' OR 1=1 --")->assertSessionHasErrors('status');
    }

    public function test_dashboard_shows_student_total(): void
    {
        $this->actingAs($this->staff)->get('/staff')->assertOk()->assertSee('Registered students')->assertSee('Register student');
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Registered students');
    }
}
