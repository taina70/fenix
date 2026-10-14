<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    public function index(): JsonResponse
    {
        $exams = Exam::withCount('questions')->get();
        return response()->json($exams);
    }

    public function show(Exam $exam): JsonResponse
    {
        $exam->load(['questions' => function ($query) {
            $query->with(['options' => function ($q) {
                $q->select('id', 'question_id', 'text'); // Oculta o is_correct para o aluno
            }]);
        }]);

        return response()->json($exam);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'created_by' => 'required|exists:users,id',
            'questions' => 'required|array|min:1',
            'questions.*.statement' => 'required|string',
            'questions.*.points' => 'required|integer|min:1',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.text' => 'required|string',
            'questions.*.options.*.is_correct' => 'required|boolean',
        ]);

        $exam = Exam::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'created_by' => $validated['created_by'],
        ]);

        foreach ($validated['questions'] as $qData) {
            $question = $exam->questions()->create([
                'statement' => $qData['statement'],
                'points' => $qData['points'],
            ]);

            foreach ($qData['options'] as $oData) {
                $question->options()->create($oData);
            }
        }

        return response()->json($exam->load('questions.options'), 201);
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $exam->delete();
        return response()->json(['message' => 'Prova removida com sucesso.']);
    }

    public function submit(Request $request, Exam $exam): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'answers' => 'required|array',
        ]);

        $attempt = $this->examService->submitAttempt(
            $exam,
            $validated['student_id'],
            $validated['answers']
        );

        return response()->json([
            'message' => 'Prova enviada e corrigida com sucesso!',
            'attempt' => $attempt,
        ], 201);
    }
}
