<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    protected $fillable = [
        'name', 'email', 'role'
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    public function createdExams(): HasMany
    {
        return $this->hasMany(Exam::class, 'created_by');

    }

    public function attempts (): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'student_id');

    }

    public function isTeachcer (): boll
    {
         return $this->role === 'teacher';

    }

    public function isStudent (): boll
    {
        return $this->role === 'student';

    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
