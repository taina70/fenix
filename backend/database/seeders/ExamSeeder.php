<?php
namespace Database\Seeders;

use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Database\Seeder;


class ExamSeeder extends Seeder
{
    public function run() : void {
        $teacher = User::where('role', 'teacher')->first();
        if (!$teacher){
            return;
        }

        $exam = Exam::create([
            'title' => 'Avaliação',
            'description' => 'teste',
            'created_by' => $teacher->id,
        ]);

        $q1 = Question::create([
            'exam_id' => $exam->id,
            'statement' => 'Quais times são premier league?',
            'points' => 2,
        ]);

        Option::create(['question_id' => $q1->id, 'text' => 'Chelsea', 'is_correct'=> true]);
        Option::create(['question_id' => $q1->id, 'text' => 'São Paulo', 'is_correct'=> false]);
        Option::create(['question_id' => $q1->id, 'text' => 'Santos', 'is_correct'=> false]);
        Option::create(['question_id' => $q1->id, 'text' => 'Leon', 'is_correct'=> false]);


        $q2 = Question::create([
            'exam_id' => $exam->id,
            'statement' => 'Quais times são do campeonato brasileiro? ',
            'points' => 3,
        ]);

        Option::create(['question_id' => $q2->id, 'text' => 'Palmeiras', 'is_correct'=> true]);
        Option::create(['question_id' => $q2->id, 'text' => 'Manchest city', 'is_correct'=> false]);
        Option::create(['question_id' => $q2->id, 'text' => 'Napoles', 'is_correct'=> false]);
        Option::create(['question_id' => $q2->id, 'text' => 'CRB', 'is_correct'=> false]);


         $q3 = Question::create([
            'exam_id' => $exam->id,
            'statement' => 'A que time pertence Bruno Fernandes',
            'points' => 5,
        ]);

        Option::create(['question_id' => $q3->id, 'text' => 'Seleção Brasileira', 'is_correct'=> true]);
        Option::create(['question_id' => $q3->id, 'text' => 'Real Madrid', 'is_correct'=> false]);
        Option::create(['question_id' => $q3->id, 'text' => 'Leon', 'is_correct'=> false]);
        Option::create(['question_id' => $q3->id, 'text' => 'Manchest United', 'is_correct'=> false]);



    }
}
