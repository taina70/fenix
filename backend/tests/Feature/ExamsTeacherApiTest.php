<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExamsTeacherApiTest extends TestCase
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
    public function nao_permite_criar_prova_sem_campos_obrigatorios()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/exams', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'created_by']);
    }

    #[Test]
    public function nao_permite_submeter_prova_sem_respostas_ou_sem_aluno()
    {
        $exam = Exam::create([
            'title' => 'Prova de História',
            'created_by' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->student)
            ->postJson("/api/exams/{$exam->id}/submit", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id', 'answers']);
    }

    #[Test]
    public function retorna_404_ao_submeter_para_prova_inexistente()
    {
        $response = $this->actingAs($this->student)
            ->postJson('/api/exams/999999/submit', [
                'student_id' => $this->student->id,
                'answers' => [1 => 1],
            ]);

        $response->assertStatus(404);
    }
}
