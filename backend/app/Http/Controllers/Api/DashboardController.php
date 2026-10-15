<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
class DashboardController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    #[OA\Get(
        path: "/api/dashboard",
        summary: "Obtém métricas gerais ou filtradas de uma prova (com cache no Redis)",
        tags: ["Dashboard"],
        parameters: [
            new OA\Parameter(
                name: "exam_id",
                in: "query",
                required: false,
                description: "ID opcional da prova para filtrar métricas específicas",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Estatísticas do dashboard retornadas com sucesso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "total_exams", type: "integer", example: 5),
                        new OA\Property(property: "total_attempts", type: "integer", example: 12),
                        new OA\Property(property: "average_score", type: "number", format: "float", example: 8.5),
                        new OA\Property(
                            property: "ranking",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "student_name", type: "string", example: "João Silva"),
                                    new OA\Property(property: "score", type: "number", example: 10)
                                ]
                            )
                        )
                    ]
                )
            )
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $examId = $request->query('exam_id');
        $stats = $this->examService->getDashboardStats($examId ? (int)$examId : null);

        return response()->json($stats);
    }
}
