<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $guarded = ['id'];

    public const STATUSES = ['Enrolled', 'Not Enrolled', 'Graduated', 'Dropped', 'LOA'];

    /** Deleted (hidden) students must never open by URL. */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)->where('is_deleted', false)->firstOrFail();
    }

    public function scopeVisible($query)
    {
        return $query->where('is_deleted', false);
    }

    /** Search box + course / year level / section / status filters. Values are always bound, never concatenated into SQL. */
    public function scopeFilter($query, array $f)
    {
        $term = trim((string) ($f['q'] ?? ''));
        if ($term !== '') {
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
            $query->where(function ($w) use ($like) {
                foreach (['student_number', 'first_name', 'middle_name', 'last_name', 'email'] as $col) {
                    $w->orWhereRaw("$col LIKE ? ESCAPE '!'", [$like]);
                }
            });
        }
        foreach (['course_id', 'year_level_id', 'section_id'] as $key) {
            if (! empty($f[$key])) {
                $query->where($key, (int) $f[$key]);
            }
        }
        if (! empty($f['status']) && in_array($f['status'], self::STATUSES, true)) {
            $query->where('enrollment_status', $f['status']);
        }

        return $query;
    }

    public function fullName(): string
    {
        return trim($this->last_name . ', ' . $this->first_name . ' ' . $this->middle_name);
    }

    protected function casts(): array
    {
        return ['birthdate' => 'date', 'date_enrolled' => 'date', 'is_deleted' => 'boolean'];
    }

    public function user()      { return $this->belongsTo(User::class); }
    public function course()    { return $this->belongsTo(Course::class); }
    public function yearLevel() { return $this->belongsTo(YearLevel::class); }
    public function section()   { return $this->belongsTo(Section::class); }
    public function grades()    { return $this->hasMany(Grade::class); }
}
