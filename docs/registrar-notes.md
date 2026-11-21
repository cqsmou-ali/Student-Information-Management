# Registrar Staff module: notes

CvSU CEIT Student Information Management System. This file records how the Registrar (Staff) role
works and the decisions behind it, where the requirements PDF is silent or unclear.
Scope: Registrar Staff only. Student-only and Admin-only features are not covered here.

## 1. Decisions

| Topic | Decision |
|---|---|
| "Academic information" (G-4) | Course, year level and section. **Grades are out of scope.** The existing grades table and read-only grades display on the student page stay. |
| Student accounts (G-6) | The Registrar creates the student's login when registering the student (username = student number, temporary password, must change it at first login). |
| Admin access to Registrar pages | **No.** Registrar pages are for the `staff` role only. Admin gets 403. |
| "Manage courses and sections: limited" (R11) | **View-only.** The Registrar can view courses, year levels and sections with student counts, and assign a student to a section. Adding, editing or deleting courses and sections is Admin work. |
| Visitor time-out (U2) | Skipped. |
| Reason for change on identity fields | **Yes.** See section 3. |

## 2. Student information the Registrar may change ("authorized", R7)

| Field | Registrar can change? | Where | Notes |
|---|---|---|---|
| Student number | **No** | — | Set at registration. It is also the student's username. |
| First, middle, last name | Yes, **with a reason** | Edit profile | See section 3. |
| Birthdate | Yes, **with a reason** | Edit profile | |
| Sex | Yes, **with a reason** | Edit profile | |
| Email | Yes | Edit profile | Also changes the student's login email (password reset links go there). Must not belong to another account. |
| Contact number, address, emergency contact | Yes | Edit profile | |
| Course, year level, section | Yes | Edit enrollment | The section must belong to the chosen course and year level. |
| Enrollment status, date enrolled | Yes | Edit enrollment | Status: Enrolled, Not Enrolled, Graduated, Dropped, LOA. |
| Grades | No | — | Out of scope. |
| Delete / hide a student | **No** | — | Admin only. |
| Student account (password, active/inactive, role) | No | — | Created once at registration; after that it is Admin work. |

Anything else sent with a form is ignored. The student model only accepts the fields above
(`Student::$fillable`); `user_id` and `is_deleted` can never be set from a form.

## 3. Reason for change

- Changing the **first, middle or last name, birthdate or sex** requires a reason (5 to 255 characters).
- The reason is only required when one of those values actually changes. Editing only the email or
  contact details needs no reason. A change in capitalisation (`juan` → `Juan`) counts as a change.
- The reason is saved in the activity log entry (`field_changes.change_reason`, and at the end of
  `details`), next to the old and new values. It is not stored on the student record.

## 4. What is logged (activity_logs)

Every entry has the user, role, IP address and time. Registrar entries about one student are linked to
it (`subject_type` / `subject_id`), so `$student->activityLogs` lists everything that happened to it.

| Action | When | Extra detail |
|---|---|---|
| `student_registered` | New student saved | — |
| `student_viewed` | Student record opened | — |
| `student_updated` | Profile saved | `field_changes`: old → new value of every changed field, plus `change_reason` |
| `enrollment_updated` | Enrollment saved | `field_changes`: old → new course / year / section / status / date |
| `report_generated` | Student list, summary or information sheet opened | Sheet entries are linked to the student |
| `report_exported` | CSV downloaded | — |
| `access_denied` | A non-staff user tried a Registrar URL | — |

The log is **append-only** in the application: `ActivityLog` refuses updates and deletes. The Registrar
cannot view the log (R13); only the Admin should.

## 5. Two registrars editing the same student

Each edit form remembers the version of the record when it was opened. If someone else saved the
record in the meantime, the save is refused with a warning and nothing is changed. The typed edits stay
in the form; the registrar can reload to see the latest version, or save again to replace the other
change on purpose. (Limitation: two saves within the same second are not detected.)

## 6. Reports

| Report | Where | Output |
|---|---|---|
| Student list (filter by search, course, year, section, status) | Reports | Screen, Print, CSV |
| Summary by course, year level and section (filter by status) | Reports → Summary by section | Screen, Print |
| Student information sheet (personal + enrollment, no grades) | Student page → Information sheet | Screen, Print |

## 7. Session and deployment settings (R19)

Local development uses `.env` as is. On the production server (HTTPS), set:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
SESSION_LIFETIME=30
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

then run `php artisan config:cache`. Turning on `SESSION_ENCRYPT` signs everyone out once.
`SESSION_SECURE_COOKIE=true` stops login from working over plain `http://`. Generate a new `APP_KEY` on
the server and never share or commit `.env`.

Independently of these settings, the new student's temporary password is kept **encrypted** in the
session, shown once, and the page showing it is sent with `Cache-Control: no-store`.

## 8. Updating an existing database (no reset)

```
copy database\database.sqlite database\database.backup-registrar.sqlite
php artisan migrate
```

This adds the audit-log columns and the staff profile columns. It does not change existing data.
To fill in the staff profile of existing registrar accounts (the seeder only does this for a fresh database):

```
php artisan tinker --execute="App\Models\User::where('username','registrar1')->update(['full_name'=>'Rosa M. Villanueva','position'=>'Registrar Staff','office'=>'CEIT Office of the College Registrar']);"
```

Never run `migrate:fresh`, `migrate:refresh` or `db:wipe` on a database with real records.
