<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class ExamController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    #[OA\Get(
        path: "/api/exams",
        summary: "Lista todas as provas cadastradas",
        tags: ["Provas"],
        responses: [
            new OA\Response(response: 200, description: "Lista de provas retornada com sucesso")
        ]
    )]

    public function index(): JsonResponse
    {
        $exams = Exam::withCount('questions')->get();
        return response()->json($exams);
    }

    #[OA\Get(
        path: "/api/exams/{exam}",
        summary: "Exibe os detalhes e questões de uma prova (ocultando respostas corretas)",
        tags: ["Provas"],
        parameters: [
            new OA\Parameter(name: "exam", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Detalhes da prova"),
            new OA\Response(response: 404, description: "Prova não encontrada")
        ]
    )]
    public function show(Exam $exam): JsonResponse
    {
        $exam->load(['questions' => function ($query) {
            $query->with(['options' => function ($q) {
                $q->select('id', 'question_id', 'text'); // Oculta o is_correct para o aluno
            }]);
        }]);

        return response()->json($exam);
    }

    #[OA\Post(
        path: "/api/exams",
        summary: "Cria uma nova prova com questões e opções",
        tags: ["Provas"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "created_by", "questions"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Prova de Matemática"),
                    new OA\Property(property: "description", type: "string", example: "Avaliação 1º Trimestre"),
                    new OA\Property(property: "created_by", type: "integer", example: 1),
                    new OA\Property(
                        property: "questions",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "statement", type: "string", example: "Quanto é 5x5?"),
                                new OA\Property(property: "points", type: "integer", example: 10),
                                new OA\Property(
                                    property: "options",
                                    type: "array",
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: "text", type: "string", example: "25"),
                                            new OA\Property(property: "is_correct", type: "boolean", example: true)
                                        ]
                                    ),
                                    example: [
                                        ["text" => "25", "is_correct" => true],
                                        ["text" => "20", "is_correct" => false]
                                    ]
                                )
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Prova criada com sucesso"),
            new OA\Response(response: 422, description: "Erro de validação nos campos")
        ]
    )]

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

        // Limpa o cache das estatísticas do Redis para refletir no Dashboard
        Cache::forget("dashboard_stats_null");

        return response()->json($exam->load('questions.options'), 201);
    }

    #[OA\Delete(
        path: "/api/exams/{exam}",
        summary: "Remove uma prova cadastrada",
        tags: ["Provas"],
        parameters: [
            new OA\Parameter(name: "exam", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Prova removida com sucesso"),
            new OA\Response(response: 404, description: "Prova não encontrada")
        ]
    )]

    public function destroy(Exam $exam): JsonResponse
    {
        $exam->delete();

        Cache::forget("dashboard_stats_null");
        Cache::forget("dashboard_stats_{$exam->id}");

        return response()->json(['message' => 'Prova removida com sucesso.']);
    }

    #[OA\Post(
        path: "/api/exams/{exam}/submit",
        summary: "Submete as respostas de um aluno para uma prova",
        tags: ["Provas"],
        parameters: [
            new OA\Parameter(name: "exam", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["student_id", "answers"],
                properties: [
                    new OA\Property(property: "student_id", type: "integer", example: 2),
                    new OA\Property(
                        property: "answers",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "question_id", type: "integer", example: 1),
                                new OA\Property(property: "option_id", type: "integer", example: 2)
                            ]
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Prova enviada e corrigida com sucesso"),
            new OA\Response(response: 422, description: "Submissão duplicada ou campos inválidos")
        ]
    )]

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

        Cache::forget("dashboard_stats_null");
        Cache::forget("dashboard_stats_{$exam->id}");

        return response()->json([
            'message' => 'Prova enviada e corrigida com sucesso!',
            'score' => $attempt->score ?? $attempt['score'],
            'total_points' => $attempt->total_points ?? $attempt['total_points'],
            'percentage' => $attempt->percentage ?? $attempt['percentage'],
            'attempt' => $attempt,
        ], 201);
    }
}
