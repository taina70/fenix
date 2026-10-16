<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'professor@teste.com')->first();

        if (!$teacher) {
            return;
        }

        $exam = Exam::firstOrCreate(
            [
                'title' => 'Avaliação',
                'created_by' => $teacher->id,
            ],
            [
                'description' => 'teste',
            ]
        );

        $questions = [
            [
                'statement' => 'Quais times são premier league?',
                'points' => 2,
                'options' => [
                    ['Chelsea', true],
                    ['São Paulo', false],
                    ['Santos', false],
                    ['Leon', false],
                ],
            ],
            [
                'statement' => 'Quais times são do campeonato brasileiro?',
                'points' => 3,
                'options' => [
                    ['Palmeiras', true],
                    ['Manchest city', false],
                    ['Napoles', false],
                    ['CRB', false],
                ],
            ],
            [
                'statement' => 'A que time pertence Bruno Fernandes',
                'points' => 5,
                'options' => [
                    ['Seleção Brasileira', true],
                    ['Real Madrid', false],
                    ['Leon', false],
                    ['Manchest United', false],
                ],
            ],
        ];

        foreach ($questions as $questionData) {
            $question = Question::firstOrCreate(
                [
                    'exam_id' => $exam->id,
                    'statement' => $questionData['statement'],
                ],
                [
                    'points' => $questionData['points'],
                ]
            );

            foreach ($questionData['options'] as [$text, $isCorrect]) {
                Option::firstOrCreate(
                    [
                        'question_id' => $question->id,
                        'text' => $text,
                    ],
                    [
                        'is_correct' => $isCorrect,
                    ]
                );
            }
        }
    }
}
