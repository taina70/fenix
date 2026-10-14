<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class AttemptAnswer extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'selected_option_id'
    ];

    public function attempt():BelongsTo{

        return $this->belongsTo(ExamAttemp::class, 'attemp_id');
    }

    public function question():BelongsTo{

        return $this->belongsTo(Question::class);
    }

    public function selectedOption():BelongsTo{

        return $this->belongsTo(Option::class, 'selected_option_id');
    }

}
