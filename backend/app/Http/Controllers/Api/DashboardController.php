<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected ExamService $examService) {}

    public function index(Request $request): JsonResponse
    {
        $examId = $request->query('exam_id');
        $stats = $this->examService->getDashboardStats($examId ? (int)$examId : null);

        return response()->json($stats);
    }
}
