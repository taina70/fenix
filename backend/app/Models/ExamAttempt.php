<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class ExamAttemp extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
            'exam_id',
            'student_id',
            'score',
            'total_points',
            'percentage',
            'completed_at',
        ];


        protected $casts =[

            'score' => 'integer',
            'total_points' => 'integer',
            'percentage' => 'float',
            'completed_at'=> 'datetime'

        ];

        public function exam():BelongsTo{

            return $this->belongsTo(Exam::class);
        }

        public function student():BelongsTo{

            return $this->belongsTo(User::class);
        }
         public function asnwers():HasMany{
            return $this->hasMany(AttemptAnswer::class, 'attempt_id');

        }

}
