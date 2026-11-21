<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable; // needed to send the password reset email

    protected $fillable = ['username', 'email', 'password_hash', 'role', 'is_active', 'must_change_password', 'full_name', 'position', 'office'];

    protected $hidden = ['password_hash', 'remember_token'];

    /** Our password column is called password_hash (as in the project specification). */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    protected function casts(): array
    {
        return [
            'password_hash'        => 'hashed',
            'is_active'            => 'boolean',
            'must_change_password' => 'boolean',
            'locked_until'         => 'datetime',
            'last_login'           => 'datetime',
        ];
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /** Where this user lands after login. */
    public function homeRoute(): string
    {
        return $this->role . '.dashboard';
    }
}
