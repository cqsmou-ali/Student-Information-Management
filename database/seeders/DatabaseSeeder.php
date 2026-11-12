<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Models\YearLevel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data: 1 admin, 2 registrars (staff), 5 students, 3 courses, 4 year levels, 7 sections.
 * Passwords are hashed with Hash::make (bcrypt) at seed time.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Courses
        $bsit  = Course::create(['course_code' => 'BSIT',  'course_name' => 'Bachelor of Science in Information Technology', 'description' => 'Four-year IT program']);
        $bscs  = Course::create(['course_code' => 'BSCS',  'course_name' => 'Bachelor of Science in Computer Science', 'description' => 'Four-year computer science program']);
        $bscpe = Course::create(['course_code' => 'BSCpE', 'course_name' => 'Bachelor of Science in Computer Engineering', 'description' => 'Four-year computer engineering program']);

        // Year levels 1 to 4
        $y = [];
        foreach ([1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'] as $n => $name) {
            $y[$n] = YearLevel::create(['name' => $name]);
        }

        // Sections
        $s = [
            'it1a' => Section::create(['section_name' => '1-A', 'course_id' => $bsit->id,  'year_level_id' => $y[1]->id]),
            'it1b' => Section::create(['section_name' => '1-B', 'course_id' => $bsit->id,  'year_level_id' => $y[1]->id]),
            'it2a' => Section::create(['section_name' => '2-A', 'course_id' => $bsit->id,  'year_level_id' => $y[2]->id]),
            'cs1a' => Section::create(['section_name' => '1-A', 'course_id' => $bscs->id,  'year_level_id' => $y[1]->id]),
            'cs2a' => Section::create(['section_name' => '2-A', 'course_id' => $bscs->id,  'year_level_id' => $y[2]->id]),
            'ce1a' => Section::create(['section_name' => '1-A', 'course_id' => $bscpe->id, 'year_level_id' => $y[1]->id]),
            'ce3a' => Section::create(['section_name' => '3-A', 'course_id' => $bscpe->id, 'year_level_id' => $y[3]->id]),
        ];

        // Admin and staff
        User::create(['username' => 'admin', 'email' => 'admin@cvsu.edu.ph', 'password_hash' => 'Admin@12345', 'role' => 'admin']);
        foreach (['registrar1', 'registrar2'] as $name) {
            User::create(['username' => $name, 'email' => "$name@cvsu.edu.ph", 'password_hash' => 'Staff@12345', 'role' => 'staff']);
        }

        // Students: username = student number, temporary password, must change it at first login
        $students = [
            ['2023-0001', 'Juan',  'Santos', 'Dela Cruz', '2004-03-15', 'Male',   'Indang, Cavite',   $bsit,  $y[2], $s['it2a'], '2023-08-14'],
            ['2023-0002', 'Maria', 'Lopez',  'Santos',    '2004-07-22', 'Female', 'Alfonso, Cavite',  $bscs,  $y[2], $s['cs2a'], '2023-08-14'],
            ['2024-0003', 'Jose',  'Cruz',   'Reyes',     '2005-01-10', 'Male',   'Mendez, Cavite',   $bsit,  $y[1], $s['it1a'], '2024-08-12'],
            ['2024-0004', 'Ana',   'Marie',  'Garcia',    '2005-11-05', 'Female', 'Silang, Cavite',   $bscpe, $y[1], $s['ce1a'], '2024-08-12'],
            ['2022-0005', 'Pedro', 'Ramos',  'Lopez',     '2003-09-30', 'Male',   'Tagaytay, Cavite', $bscpe, $y[3], $s['ce3a'], '2022-08-15'],
        ];
        $made = [];
        foreach ($students as $i => [$no, $first, $mid, $last, $bday, $sex, $addr, $course, $year, $section, $enrolled]) {
            $email = strtolower($first . '.' . str_replace(' ', '', $last)) . '@student.cvsu.edu.ph';
            $user = User::create([
                'username' => $no, 'email' => $email, 'password_hash' => 'Student@12345',
                'role' => 'student', 'must_change_password' => true,
            ]);
            $made[$i] = Student::create([
                'user_id' => $user->id, 'student_number' => $no, 'first_name' => $first, 'middle_name' => $mid,
                'last_name' => $last, 'birthdate' => $bday, 'sex' => $sex, 'email' => $email,
                'contact_number' => '0917123450' . ($i + 1), 'address' => $addr,
                'emergency_contact' => 'Guardian of ' . $first . ' - 0918123450' . ($i + 1),
                'course_id' => $course->id, 'year_level_id' => $year->id, 'section_id' => $section->id,
                'enrollment_status' => 'Enrolled', 'date_enrolled' => $enrolled,
            ]);
        }

        // Subjects and a few grades (academic information view)
        $sub = [
            Subject::create(['subject_code' => 'ITEC 50', 'subject_title' => 'Web Systems and Technologies', 'units' => 3, 'course_id' => $bsit->id,  'year_level_id' => $y[2]->id, 'semester' => '1st']),
            Subject::create(['subject_code' => 'ITEC 55', 'subject_title' => 'Platform Technologies',       'units' => 3, 'course_id' => $bsit->id,  'year_level_id' => $y[2]->id, 'semester' => '1st']),
            Subject::create(['subject_code' => 'COSC 55', 'subject_title' => 'Discrete Structures II',      'units' => 3, 'course_id' => $bscs->id,  'year_level_id' => $y[2]->id, 'semester' => '1st']),
            Subject::create(['subject_code' => 'DCIT 22', 'subject_title' => 'Computer Programming I',      'units' => 3, 'course_id' => $bsit->id,  'year_level_id' => $y[1]->id, 'semester' => '1st']),
            Subject::create(['subject_code' => 'CPEN 21', 'subject_title' => 'Logic Circuits and Design',   'units' => 3, 'course_id' => $bscpe->id, 'year_level_id' => $y[1]->id, 'semester' => '1st']),
        ];
        foreach ([[0, 0, 1.50], [0, 1, 1.75], [1, 2, 2.00], [2, 3, 1.25], [3, 4, 2.25]] as [$st, $su, $g]) {
            Grade::create(['student_id' => $made[$st]->id, 'subject_id' => $sub[$su]->id, 'school_year' => '2025-2026', 'semester' => '1st', 'grade' => $g, 'remarks' => 'Passed']);
        }
    }
}
