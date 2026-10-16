<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentHistoryApiTest extends TestCase
{
    use RefreshDatabase;

   public function aluno_pode_consultar_seu_historico_de_provas(): void
{
    $student = User::factory()->create(['role' => 'student']);
    $teacher = User::factory()->create(['role' => 'teacher']);

    $exam = Exam::create([
        'title' => 'Prova de História',
        'created_by' => $teacher->id,
    ]);

    $attempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $student->id,
        'score' => 7,
        'total_points' => 10,
        'percentage' => 70,
        'completed_at' => now(),
    ]);

    $response = $this->getJson(
        "/api/students/{$student->id}/attempts"
    );

    $response->assertOk()
        ->assertJsonPath('data.0.id', $attempt->id)
        ->assertJsonPath('data.0.exam_id', $exam->id)
        ->assertJsonPath('data.0.student_id', $student->id)
        ->assertJsonPath('data.0.score', 7)
        ->assertJsonPath('data.0.total_points', 10)
        ->assertJsonPath('data.0.exam.id', $exam->id)
        ->assertJsonPath('data.0.exam.title', 'Prova de História');
}

    public function test_historico_vazio_retorna_lista_vazia(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->getJson(
            "/api/students/{$student->id}/attempts"
        );

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
