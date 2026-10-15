<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\AttemptAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ExamService
{

    public function submitAttempt(Exam $exam, int $studentId, array $answersData): ExamAttempt
    {

        $alreadyAttempted = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadyAttempted) {
            throw ValidationException::withMessages([
                'attempt' => ['Você já realizou esta avaliação e não pode responder novamente.'],
            ]);
        }

        return DB::transaction(function () use ($exam, $studentId, $answersData) {
            $exam->load('questions.correctOption');

            $score = 0;
            $totalPoints = $exam->questions->sum('points');
            $answersToInsert = [];

            foreach ($exam->questions as $question) {
                $selectedOptionId = $answersData[$question->id] ?? null;
                $correctOption = $question->correctOption;

                if ($selectedOptionId && $correctOption && $correctOption->id == $selectedOptionId) {
                    $score += $question->points;
                }

                if ($selectedOptionId) {
                    $answersToInsert[] = [
                        'question_id' => $question->id,
                        'selected_option_id' => $selectedOptionId,
                    ];
                }
            }

            $percentage = $totalPoints > 0 ? round(($score / $totalPoints) * 100, 2) : 0;

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $studentId,
                'score' => $score,
                'total_points' => $totalPoints,
                'percentage' => $percentage,
                'completed_at' => now(),
            ]);

            foreach ($answersToInsert as $answer) {
                AttemptAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $answer['question_id'],
                    'selected_option_id' => $answer['selected_option_id'],
                ]);
            }


            Cache::forget("dashboard:exam:{$exam->id}");
            Cache::forget('dashboard:global:stats');

            return $attempt->load('answers');
        });
    }

    public function getDashboardStats(?int $examId = null): array
    {
        $cacheKey = $examId ? "dashboard:exam:{$examId}" : 'dashboard:global:stats';

        return Cache::remember($cacheKey, 600, function () use ($examId) {
            $query = ExamAttempt::with(['student', 'exam']);

            if ($examId) {
                $query->where('exam_id', $examId);
            }

            $attempts = $query->get();

            if ($attempts->isEmpty()) {
                return [
                    'average_score' => 0,
                    'top_score' => null,
                    'ranking' => [],
                ];
            }

            $averageScore = round($attempts->avg('percentage'), 2);
            $topAttempt = $attempts->sortByDesc('score')->first();

            $ranking = ExamAttempt::with(['student', 'exam'])
                ->when($examId, fn($q) => $q->where('exam_id', $examId))
                ->orderByDesc('score')
                ->orderBy('completed_at')
                ->paginate(10)
                ->toArray();

            return [
                'average_score' => $averageScore,
                'top_score' => [
                    'student_name' => $topAttempt->student->name,
                    'score' => $topAttempt->score,
                    'total_points' => $topAttempt->total_points,
                    'percentage' => $topAttempt->percentage,
                ],
                'ranking' => $ranking,
            ];
        });
    }
}
