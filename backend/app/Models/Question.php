<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class Question extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'exam_id',
        'statement',
        'points'
    ];

    protected $casts = [
        'points' => 'integer'
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);

    }
     public function options(): HasMany
    {
        return $this->hasMany(Question::class);

    }

     public function attempts(): HasMany
    {
        return $this->hasMany(Option::class);

    }

    public function correctOption(): HasMany
    {
        return $this->hasMany(Option::class)->where('is_correct', true);

    }
}
