<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;
use App\Models\ExamAttempt;

class ExamController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    #[OA\Get(
        path: "/api/exams",
        summary: "Lista todas as provas cadastradas",
        tags: ["Provas"],
        parameters: [
            new OA\Parameter(
                name: "student_id",
                in: "query",
                required: false,
                description: "ID do aluno para retornar a nota e status de submissão (has_submitted)",
                schema: new OA\Schema(type: "integer", example: 2)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de provas retornada com sucesso")
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $studentId = $request->query('student_id');

        $exams = Exam::withCount('questions')->get()->map(function ($exam) use ($studentId) {
            $exam->has_submitted = false;
            $exam->score = null;

            if ($studentId) {
                $attempt = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($attempt) {
                    $exam->has_submitted = true;
                    $exam->score = $attempt->score;
                    $exam->percentage = $attempt->percentage;
                }
            }

            return $exam;
        });

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
        $exam->load([
            'questions' => function ($query) {
                $query->with([
                    'options' => function ($q) {
                        $q->select('id', 'question_id', 'text');
                    }
                ]);
            }
        ]);

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

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação nos campos estruturais.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validator->after(function ($validator) use ($request) {
            $questions = $request->input('questions', []);

            foreach ($questions as $index => $question) {
                $options = $question['options'] ?? [];

                $correctCount = collect($options)->where('is_correct', true)->count();

                if ($correctCount !== 1) {
                    $validator->errors()->add(
                        "questions.{$index}.options",
                        "A questão " . ($index + 1) . " deve ter exatamente uma alternativa correta. (Encontradas: {$correctCount})"
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Os dados fornecidos são inválidos.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

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
        Cache::forget('dashboard:global:stats');

        return response()->json($exam->load('questions.options'), 201);
    }

        #[OA\Put(
        path: "/api/exams/{exam}",
        summary: "Atualiza uma prova existente, suas questões e opções",
        tags: ["Provas"],
        parameters: [
            new OA\Parameter(name: "exam", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Prova atualizada com sucesso"),
            new OA\Response(response: 422, description: "Erro de validação")
        ]
    )]
    public function update(Request $request, Exam $exam): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'nullable|integer',
            'questions.*.statement' => 'required|string',
            'questions.*.points' => 'required|integer|min:1',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.id' => 'nullable|integer',
            'questions.*.options.*.text' => 'required|string',
            'questions.*.options.*.is_correct' => 'required|boolean',
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ($request->input('questions', []) as $index => $question) {
                $correctCount = collect($question['options'] ?? [])->where('is_correct', true)->count();
                if ($correctCount !== 1) {
                    $validator->errors()->add("questions.{$index}.options", "A questão " . ($index + 1) . " deve ter exatamente uma alternativa correta.");
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['message' => 'Dados inválidos.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        \Illuminate\Support\Facades\DB::transaction(function () use ($exam, $validated) {
            $exam->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            // Para simplificar e evitar inconsistências, removemos as antigas questões e opções e reinserimos as atualizadas
            $exam->questions()->delete();

            foreach ($validated['questions'] as $qData) {
                $question = $exam->questions()->create([
                    'statement' => $qData['statement'],
                    'points' => $qData['points'],
                ]);

                foreach ($qData['options'] as $oData) {
                    $question->options()->create([
                        'text' => $oData['text'],
                        'is_correct' => $oData['is_correct'],
                    ]);
                }
            }
        });

        // Limpa o cache para refletir no Dashboard e listagens
        Cache::forget('dashboard:global:stats');
        Cache::forget("dashboard_stats_{$exam->id}");

        return response()->json($exam->load('questions.options'));
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

        Cache::forget('dashboard:global:stats');
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
                type: "object",
                description: "Objeto chave-valor onde a chave é o ID da questão e o valor é o ID da opção selecionada",
                additionalProperties: new OA\AdditionalProperties(type: "integer"),
                example: ["1" => 1, "2" => 5]
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
        // 1. Cria o validador manualmente para evitar que o Laravel tente renderizar HTML
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'answers' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação nos campos estruturais.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $attempt = $this->examService->submitAttempt(
                $exam,
                $validated['student_id'],
                $validated['answers']
            );

            return response()->json([
                'message' => 'Prova enviada e corrigida com sucesso!',
                'attempt' => [
                    'score' => $attempt->score,
                    'total_points' => $attempt->total_points,
                    'percentage' => $attempt->percentage,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Os dados fornecidos são inválidos.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    #[OA\Get(
    path: "/api/students/{student}/attempts",
    summary: "Consulta o histórico de provas de um aluno",
    tags: ["Alunos"],
    parameters: [
        new OA\Parameter(
            name: "student",
            in: "path",
            required: true,
            description: "ID do aluno",
            schema: new OA\Schema(type: "integer", example: 2)
        ),
        new OA\Parameter(
            name: "page",
            in: "query",
            required: false,
            description: "Número da página do histórico",
            schema: new OA\Schema(type: "integer", minimum: 1, example: 1)
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Histórico de provas retornado com sucesso",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "exam_id", type: "integer", example: 1),
                                new OA\Property(property: "student_id", type: "integer", example: 2),
                                new OA\Property(property: "score", type: "number", example: 7),
                                new OA\Property(property: "total_points", type: "number", example: 10),
                                new OA\Property(property: "percentage", type: "number", example: 70),
                                new OA\Property(
                                    property: "completed_at",
                                    type: "string",
                                    format: "date-time",
                                    example: "2026-10-16T14:19:26"
                                ),
                                new OA\Property(
                                    property: "exam",
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "title", type: "string", example: "Avaliação"),
                                        new OA\Property(property: "description", type: "string", nullable: true, example: "Prova de conhecimentos")
                                    ]
                                )
                            ]
                        )
                    ),
                    new OA\Property(property: "current_page", type: "integer", example: 1),
                    new OA\Property(property: "last_page", type: "integer", example: 1),
                    new OA\Property(property: "per_page", type: "integer", example: 10),
                    new OA\Property(property: "total", type: "integer", example: 1)
                ]
            )
        )
    ]
)]

    public function studentHistory(int $student)
        {
            $attempts = ExamAttempt::query()
                ->where('student_id', $student)
                ->with('exam:id,title,description')
                ->orderByDesc('completed_at')
                ->paginate(10);

            return response()->json($attempts);
        }

}
