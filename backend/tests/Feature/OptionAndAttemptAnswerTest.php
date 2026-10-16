<?php

namespace Tests\Feature;

use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionAndAttemptAnswerTest extends TestCase
{
    use RefreshDatabase;

    private Exam $exam;
    private Question $question;
    private Option $option;
    private ExamAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->exam = Exam::create([
            'title' => 'Prova de teste',
            'description' => 'Descrição da prova',
            'created_by' => $teacher->id,
        ]);

        $this->question = Question::create([
            'exam_id' => $this->exam->id,
            'statement' => 'Quanto é 2 + 2?',
            'points' => 10,
        ]);

        $this->option = Option::create([
            'question_id' => $this->question->id,
            'text' => '4',
            'is_correct' => true,
        ]);

        $this->attempt = ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $student->id,
            'score' => 10,
            'total_points' => 10,
            'percentage' => 100,
            'completed_at' => now(),
        ]);
    }

    public function test_option_belongs_to_its_question(): void
    {
        $this->assertTrue(
            $this->option->question->is($this->question)
        );
    }

    public function test_option_casts_is_correct_to_boolean(): void
    {
        $this->assertIsBool($this->option->is_correct);
        $this->assertTrue($this->option->is_correct);
    }

    public function test_option_is_persisted_with_its_question(): void
    {
        $this->assertDatabaseHas('options', [
            'id' => $this->option->id,
            'question_id' => $this->question->id,
            'text' => '4',
            'is_correct' => true,
        ]);
    }

    public function test_attempt_answer_has_correct_relationships(): void
    {
        $answer = AttemptAnswer::create([
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->question->id,
            'selected_option_id' => $this->option->id,
        ]);

        $this->assertTrue(
            $answer->attempt->is($this->attempt)
        );

        $this->assertTrue(
            $answer->question->is($this->question)
        );

        $this->assertTrue(
            $answer->selectedOption->is($this->option)
        );
    }

    public function test_attempt_answer_is_persisted(): void
    {
        $answer = AttemptAnswer::create([
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->question->id,
            'selected_option_id' => $this->option->id,
        ]);

        $this->assertDatabaseHas('attempt_answers', [
            'id' => $answer->id,
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->question->id,
            'selected_option_id' => $this->option->id,
        ]);
    }

    public function test_attempt_has_many_answers(): void
    {
        AttemptAnswer::create([
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->question->id,
            'selected_option_id' => $this->option->id,
        ]);

        $this->assertCount(1, $this->attempt->answers);
    }

    public function test_database_rejects_duplicate_answer_for_same_question(): void
    {
        AttemptAnswer::create([
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->question->id,
            'selected_option_id' => $this->option->id,
        ]);

        $this->expectException(
            \Illuminate\Database\QueryException::class
        );

        AttemptAnswer::create([
            'attempt_id' => $this->attempt->id,
            'question_id' => $this->question->id,
            'selected_option_id' => $this->option->id,
        ]);
    }
}
