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
        // 1. Checagem inicial de segurança contra duplicidade simples
        $alreadyAttempted = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->exists();

        if ($alreadyAttempted) {
            throw ValidationException::withMessages([
                'attempt' => ['Você já realizou esta avaliação e não pode responder novamente.'],
            ]);
        }

        // === TRAVA DE SEGURANÇA ANTIFRAUDE: VALIDAÇÃO DE RELACIONAMENTO QUESTÃO -> PROVA ===
        // Carrega as questões legítimas desta avaliação específica
        $exam->load('questions.options');
        $validQuestionIds = $exam->questions->pluck('id')->toArray();

        // Varde todas as chaves (IDs das questões) enviadas na requisição
        foreach (array_keys($answersData) as $submittedQuestionId) {
            // Se o ID enviado não pertencer à lista de IDs reais da prova, bloqueia a fraude na hora
            if (!in_array($submittedQuestionId, $validQuestionIds)) {
                throw ValidationException::withMessages([
                    'answers' => ["A questão ID {$submittedQuestionId} não pertence a esta avaliação."],
                ]);
            }
        }
        // ==================================================================================

        // 2. Encapsula o fluxo em Transação para garantir integridade e tratar concorrência pura
        try {
            return DB::transaction(function () use ($exam, $studentId, $answersData) {
                $score = 0;
                $totalPoints = $exam->questions->sum('points');
                $answersToInsert = [];

                foreach ($exam->questions as $question) {
                    $selectedOptionId = $answersData[$question->id] ?? null;

                    if ($selectedOptionId) {
                        // Valida se a alternativa selecionada pertence estritamente a ESTA questão
                        $validOption = $question->options->firstWhere('id', $selectedOptionId);

                        if (!$validOption) {
                            throw ValidationException::withMessages([
                                "answers.{$question->id}" => ["A alternativa selecionada não pertence a esta questão."],
                            ]);
                        }

                        // Se a opção for válida e estiver marcada como correta, soma a pontuação
                        if ($validOption->is_correct) {
                            $score += $question->points;
                        }

                        $answersToInsert[] = [
                            'question_id' => $question->id,
                            'selected_option_id' => $selectedOptionId,
                        ];
                    }
                }

                // Calcula o percentual de aproveitamento (padrão unificado para o dashboard)
                $percentage = $totalPoints > 0 ? round(($score / $totalPoints) * 100, 2) : 0;

                // Persiste a tentativa no banco de dados
                $attempt = ExamAttempt::create([
                    'exam_id' => $exam->id,
                    'student_id' => $studentId,
                    'score' => $score,
                    'total_points' => $totalPoints,
                    'percentage' => $percentage,
                    'completed_at' => now(),
                ]);

                // Insere o histórico detalhado das respostas do aluno
                foreach ($answersToInsert as $answer) {
                    AttemptAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $answer['question_id'],
                        'selected_option_id' => $answer['selected_option_id'],
                    ]);
                }

                // Invalida as chaves do Redis para o painel de métricas atualizar em tempo real
                Cache::forget("dashboard:exam:{$exam->id}");
                Cache::forget('dashboard:global:stats');

                return $attempt->load('answers');
            });

        } catch (\Illuminate\Database\QueryException $e) {
            // 3. TRATAMENTO DE CONCORRÊNCIA: Captura o estouro de UNIQUE constraint em cliques simultâneos
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'duplicate key value')) {
                throw ValidationException::withMessages([
                    'attempt' => ['Operação bloqueada. Uma submissão para esta avaliação já está sendo processada.'],
                ]);
            }

            throw $e;
        }
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

            // Se não houver tentativas registradas, retorna a estrutura zerada com as chaves corretas
            if ($attempts->isEmpty()) {
                return [
                    'average_score' => 0,
                    'lowest_score' => 0,
                    'total_attempts' => 0,
                    'top_score' => null,
                    'ranking' => [
                        'data' => []
                    ],
                ];
            }

            // 1. Cálculos unificados baseados em Aproveitamento (%)
            $averageScore = round($attempts->avg('percentage'), 2);
            $lowestScore = round($attempts->min('percentage'), 2);
            $totalAttempts = $attempts->count();

            // Encontra a melhor tentativa baseada no maior aproveitamento percentual
            $topAttempt = $attempts->sortByDesc('percentage')->first();

            // 2. Ranking Geral ordenado de forma justa por Aproveitamento (%) e tempo de conclusão
            $ranking = ExamAttempt::with(['student', 'exam'])
                ->when($examId, fn($q) => $q->where('exam_id', $examId))
                ->orderByDesc('percentage') // Mudado de 'score' para 'percentage'
                ->orderBy('completed_at')
                ->paginate(10)
                ->toArray();

            return [
                'average_score' => $averageScore,
                'lowest_score' => $lowestScore,     // Métrica solicitada adicionada
                'total_attempts' => $totalAttempts, // Métrica solicitada adicionada
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
