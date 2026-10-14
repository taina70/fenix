<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\Option;
use App\Models\ExamAttempt;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;

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
}
