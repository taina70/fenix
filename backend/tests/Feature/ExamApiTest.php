<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExamApiTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    #[Test]
    public function pode_listar_todos_os_usuarios()
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    #[Test]
    public function professor_pode_criar_uma_prova_com_questoes_e_opcoes()
    {
        $payload = [
            'title' => 'Prova de Teste Automatizado',
            'description' => 'Descrição da prova',
            'created_by' => $this->teacher->id,
            'questions' => [
                [
                    'statement' => 'Quanto é 5x5?',
                    'points' => 10,
                    'options' => [
                        ['text' => '25', 'is_correct' => true],
                        ['text' => '20', 'is_correct' => false],
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->teacher)
            ->postJson('/api/exams', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('exams', ['title' => 'Prova de Teste Automatizado']);
        $this->assertDatabaseHas('questions', ['statement' => 'Quanto é 5x5?']);
    }

    #[Test]
    public function aluno_pode_visualizar_e_submeter_uma_prova()
    {
        $exam = Exam::create([
            'title' => 'Prova de Matemática',
            'description' => 'Matemática básica',
            'created_by' => $this->teacher->id,
        ]);

        $question = Question::create([
            'exam_id' => $exam->id,
            'statement' => 'Quanto é 10/2?',
            'points' => 10,
        ]);

        $correctOption = Option::create([
            'question_id' => $question->id,
            'text' => '5',
            'is_correct' => true,
        ]);

        $this->actingAs($this->student)
            ->getJson("/api/exams/{$exam->id}")
            ->assertStatus(200);

        $submitPayload = [
            'student_id' => $this->student->id,
            'answers' => [
                $question->id => $correctOption->id,
            ]
        ];

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$exam->id}/submit", $submitPayload);

        $response->assertStatus(201)
            ->assertJsonPath('attempt.score', 10);

        $this->actingAs($this->student)
            ->postJson("/api/exams/{$exam->id}/submit", $submitPayload)
            ->assertStatus(422);
    }
}
