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

    #[Test]
    public function ela_deve_rejeitar_uma_prova_se_uma_questao_tiver_mais_de_uma_alternativa_correta()
    {
        $payload = [
            'title' => 'Prova Teste de Validação',
            'description' => 'Testando múltiplas alternativas corretas',
            'created_by' => $this->teacher->id,
            'questions' => [
                [
                    'statement' => 'Qual é a resposta certa?',
                    'points' => 10,
                    'options' => [
                        ['text' => 'Alternativa A', 'is_correct' => true],
                        ['text' => 'Alternativa B', 'is_correct' => true],
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/exams', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['questions.0.options']);
    }

    #[Test]
    public function ele_deve_atualizar_uma_prova_com_sucesso_quando_os_dados_forem_validos()
    {
        // 1. Cria a prova inicial de forma direta
        $exam = Exam::create([
            'title' => 'Título Original',
            'description' => 'Descrição antiga',
            'created_by' => $this->teacher->id
        ]);

        $question = Question::create([
            'exam_id' => $exam->id,
            'statement' => 'Questão Antiga',
            'points' => 5
        ]);

        Option::create(['question_id' => $question->id, 'text' => 'Opção Certa', 'is_correct' => true]);
        Option::create(['question_id' => $question->id, 'text' => 'Opção Errada', 'is_correct' => false]);

        // 2. Define o payload de atualização
        $payload = [
            'title' => 'Título Atualizado via Teste',
            'description' => 'Nova descrição',
            'questions' => [
                [
                    'statement' => 'Nova Questão Modificada',
                    'points' => 10,
                    'options' => [
                        ['text' => 'Nova Opção Verdadeira', 'is_correct' => true],
                        ['text' => 'Nova Opção Falsa', 'is_correct' => false],
                    ]
                ]
            ]
        ];

        // 3. Executa a requisição PUT
        $response = $this->putJson("/api/exams/{$exam->id}", $payload);

        // 4. Asserts: Confirma o sucesso e valida a alteração
        $response->assertStatus(200);
        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'title' => 'Título Atualizado via Teste'
        ]);

        $this->assertDatabaseMissing('questions', ['statement' => 'Questão Antiga']);
        $this->assertDatabaseHas('questions', ['statement' => 'Nova Questão Modificada', 'points' => 10]);
    }

    #[Test]
    public function ele_deve_rejeitar_a_atualizacao_da_prova_se_uma_questao_tiver_mais_de_uma_alternativa_correta()
    {
        // 1. Cria a prova inicial de forma direta
        $exam = Exam::create([
            'title' => 'Prova Inicial para Erro',
            'created_by' => $this->teacher->id
        ]);

        // 2. Monta o payload inválido
        $payload = [
            'title' => 'Tentando Atualizar com Erro',
            'questions' => [
                [
                    'statement' => 'Questão com erro proposital',
                    'points' => 5,
                    'options' => [
                        ['text' => 'Opção A', 'is_correct' => true],
                        ['text' => 'Opção B', 'is_correct' => true],
                    ]
                ]
            ]
        ];

        // 3. Executa a requisição PUT
        $response = $this->putJson("/api/exams/{$exam->id}", $payload);

        // 4. Asserts: Garante o bloqueio 422
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['questions.0.options']);
    }
        #[Test]
    public function ela_deve_rejeitar_uma_prova_se_uma_questao_tiver_zero_alternativas_corretas()
    {
        $payload = [
            'title' => 'Prova Sem Resposta Certa',
            'created_by' => $this->teacher->id,
            'questions' => [
                [
                    'statement' => 'Qual é a resposta certa?',
                    'points' => 10,
                    'options' => [
                        ['text' => 'Alternativa A', 'is_correct' => false],
                        ['text' => 'Alternativa B', 'is_correct' => false], // Erro: nenhuma verdadeira!
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($this->teacher)->postJson('/api/exams', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['questions.0.options']);
    }

    #[Test]
    public function ela_deve_rejeitar_a_submissao_se_a_alternativa_pertencer_a_outra_questao_ou_prova()
    {
        // Cria a prova A legítima
        $examA = Exam::create(['title' => 'Prova A', 'created_by' => $this->teacher->id]);
        $questionA = Question::create(['exam_id' => $examA->id, 'statement' => 'Questão A', 'points' => 10]);
        $optionA = Option::create(['question_id' => $questionA->id, 'text' => 'Opção A', 'is_correct' => true]);

        // Cria a prova B intrusa
        $examB = Exam::create(['title' => 'Prova B', 'created_by' => $this->teacher->id]);
        $questionB = Question::create(['exam_id' => $examB->id, 'statement' => 'Questão B', 'points' => 10]);
        $optionB = Option::create(['question_id' => $questionB->id, 'text' => 'Opção B', 'is_correct' => true]);

        // Aluno tenta responder a Prova A usando o ID da alternativa da Prova B
        $payload = [
            'student_id' => $this->student->id,
            'answers' => [
                $questionA->id => $optionB->id // Injeção maliciosa
            ]
        ];

        $response = $this->actingAs($this->student)->postJson("/api/exams/{$examA->id}/submit", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['answers.' . $questionA->id]);
    }


    #[Test]
    public function ela_deve_garantir_apenas_uma_tentativa_registrada_sob_requisicoes_simultaneas()
    {
        $exam = Exam::create(['title' => 'Prova Concorrente', 'created_by' => $this->teacher->id]);
        $question = Question::create(['exam_id' => $exam->id, 'statement' => '1+1?', 'points' => 10]);
        $option = Option::create(['question_id' => $question->id, 'text' => '2', 'is_correct' => true]);

        // Simula o exato milissegundo de concorrência onde a primeira requisição acabou de salvar no banco,
        // mas a segunda requisição simultânea tenta burlar a trava de software (furando o exists)
        \App\Models\ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => 10,
            'total_points' => 10,
            'percentage' => 100,
            'completed_at' => now(),
        ]);

        $payload = [
            'student_id' => $this->student->id,
            'answers' => [$question->id => $option->id]
        ];

        // Segunda requisição bate na API
        $response = $this->actingAs($this->student)->postJson("/api/exams/{$exam->id}/submit", $payload);

        // O catch da QueryException (Erro 23505 do Postgres) deve interceptar e retornar um erro amigável 422
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attempt']);

        // Ponto 6: Confirma que apenas 1 registro único existe no banco de dados histórico
        $this->assertEquals(1, \App\Models\ExamAttempt::where('exam_id', $exam->id)->where('student_id', $this->student->id)->count());
    }

    #[Test]
    public function ela_deve_excluir_uma_prova_existente_com_sucesso()
    {
        $exam = Exam::create(['title' => 'Prova para Deletar', 'created_by' => $this->teacher->id]);

        $response = $this->actingAs($this->teacher)->deleteJson("/api/exams/{$exam->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    #[Test]
    public function ela_retorna_404_ao_tentar_excluir_uma_prova_inexistente()
    {
        $response = $this->actingAs($this->teacher)->deleteJson('/api/exams/999999');

        $response->assertStatus(404);
    }

    #[Test]
    public function test_detalhes_da_prova_nao_exibem_gabarito(): void
{
    $teacher = \App\Models\User::factory()->create([
        'role' => 'teacher',
    ]);

    $exam = \App\Models\Exam::create([
        'title' => 'Prova de teste',
        'created_by' => $teacher->id,
    ]);

    $question = $exam->questions()->create([
        'statement' => 'Quanto é 2 + 2?',
        'points' => 10,
    ]);

    $question->options()->createMany([
        ['text' => '4', 'is_correct' => true],
        ['text' => '5', 'is_correct' => false],
    ]);

    $response = $this->getJson("/api/exams/{$exam->id}");

    $response->assertOk()
        ->assertJsonMissingPath('questions.0.options.0.is_correct')
        ->assertJsonMissingPath('questions.0.options.1.is_correct');
}

}
