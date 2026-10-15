<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Exam Management API",
    description: "API para gestão, execução e métricas de provas automatizadas."
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Servidor Local"
)]
abstract class Controller
{
    //
}
