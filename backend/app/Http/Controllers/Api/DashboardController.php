<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    public function __construct(
        protected ExamService $examService
    ) {}

    #[OA\Get(
        path: "/api/dashboard",
        summary: "Retorna as métricas e o ranking das provas",
        tags: ["Dashboard"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Métricas retornadas com sucesso"
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $examId = $request->query('exam_id');

        $stats = $this->examService->getDashboardStats(
            $examId !== null ? (int) $examId : null
        );

        $stats['total_exams'] = Exam::count();

        return response()->json($stats);
    }
}
