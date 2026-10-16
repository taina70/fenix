<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\Option;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;
use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Cache;
class ExamServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamService $examService;
    private User $student;
    private Exam $exam;
    private Question $question;
    private Option $correctOption;
    private Option $wrongOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->examService = new ExamService();

        $teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student']);

        $this->exam = Exam::create([
            'title' => 'Prova Teste',
            'description' => 'Descrição',
            'created_by' => $teacher->id,
        ]);

        $this->question = Question::create([
            'exam_id' => $this->exam->id,
            'statement' => 'Quanto é 2 + 2?',
            'points' => 10,
        ]);

        $this->correctOption = Option::create([
            'question_id' => $this->question->id,
            'text' => '4',
            'is_correct' => true,
        ]);

        $this->wrongOption = Option::create([
            'question_id' => $this->question->id,
            'text' => '5',
            'is_correct' => false,
        ]);
    }

    #[Test]
    public function calcula_corretamente_a_pontuacao_de_uma_tentativa()
    {
        $attempt = $this->examService->submitAttempt(
            $this->exam,
            $this->student->id,
            [$this->question->id => $this->correctOption->id]
        );

        $this->assertEquals(10, $attempt->score);
        $this->assertEquals(100.0, $attempt->percentage);
        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'score' => 10,
        ]);
    }

    #[Test]
    public function impede_submissao_duplicada_do_mesmo_aluno()
    {
        $this->examService->submitAttempt(
            $this->exam,
            $this->student->id,
            [$this->question->id => $this->correctOption->id]
        );

        $this->expectException(ValidationException::class);

        $this->examService->submitAttempt(
            $this->exam,
            $this->student->id,
            [$this->question->id => $this->wrongOption->id]
        );
    }

    #[Test]
    public function calcula_zero_pontos_quando_aluno_seleciona_alternativa_errada(): void
    {
        $attempt = $this->examService->submitAttempt(
            $this->exam,
            $this->student->id,
            [$this->question->id => $this->wrongOption->id]
        );

        $this->assertEquals(0, $attempt->score);
        $this->assertEquals(10, $attempt->total_points);
        $this->assertEquals(0, $attempt->percentage);

        $this->assertDatabaseHas('attempt_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $this->question->id,
            'selected_option_id' => $this->wrongOption->id,
        ]);
    }

    #[Test]
    public function permite_submissao_sem_respostas_e_registra_zero_pontos(): void
    {
        $attempt = $this->examService->submitAttempt(
            $this->exam,
            $this->student->id,
            []
        );

        $this->assertEquals(0, $attempt->score);
        $this->assertEquals(10, $attempt->total_points);
        $this->assertEquals(0, $attempt->percentage);
        $this->assertCount(0, $attempt->answers);

        $this->assertDatabaseHas('exam_attempts', [
            'id' => $attempt->id,
            'student_id' => $this->student->id,
            'score' => 0,
        ]);

        $this->assertDatabaseCount('attempt_answers', 0);
    }


#[Test]
public function retorna_metricas_zeradas_quando_nao_existem_tentativas(): void
{
    Cache::forget('dashboard:global:stats');

    $stats = $this->examService->getDashboardStats();

    $this->assertSame(0, $stats['average_score']);
    $this->assertSame(0, $stats['lowest_score']);
    $this->assertSame(0, $stats['total_attempts']);
    $this->assertNull($stats['top_score']);
    $this->assertSame([], $stats['ranking']['data']);
}

#[Test]
public function calcula_metricas_e_ranking_das_tentativas(): void
{
    Cache::forget('dashboard:global:stats');

    ExamAttempt::create([
        'exam_id' => $this->exam->id,
        'student_id' => $this->student->id,
        'score' => 8,
        'total_points' => 10,
        'percentage' => 80,
        'completed_at' => now()->subMinutes(2),
    ]);

    $secondStudent = User::factory()->create(['role' => 'student']);

    ExamAttempt::create([
        'exam_id' => $this->exam->id,
        'student_id' => $secondStudent->id,
        'score' => 6,
        'total_points' => 10,
        'percentage' => 60,
        'completed_at' => now(),
    ]);

    $stats = $this->examService->getDashboardStats();

    $this->assertEquals(70, $stats['average_score']);
    $this->assertEquals(60, $stats['lowest_score']);
    $this->assertEquals(2, $stats['total_attempts']);

    $this->assertEquals(80, $stats['top_score']['percentage']);
    $this->assertEquals(8, $stats['top_score']['score']);
    $this->assertEquals(10, $stats['top_score']['total_points']);

    $this->assertCount(2, $stats['ranking']['data']);
    $this->assertEquals(80, $stats['ranking']['data'][0]['percentage']);
}

}
