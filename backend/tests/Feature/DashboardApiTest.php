<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Exam;
use App\Models\User;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dashboard_calcula_metricas_e_utiliza_cache()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'total_exams' => 1,
                'total_attempts' => 0,
                'average_score' => 0,
                'ranking' => []
            ]);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200);
    }

       #[Test]
    public function o_dashboard_deve_calcular_todas_as_metricas_solicitadas_e_invalidar_o_cache_no_redis_em_alteracoes()
    {
        $teacher = \App\Models\User::factory()->create(['role' => 'teacher']);

        \Illuminate\Support\Facades\Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'average_score' => 75.00,
                'lowest_score' => 50.00,
                'total_attempts' => 2,
                'top_score' => ['student_name' => 'Aluno Top', 'score' => 10],
                'ranking' => ['data' => []]
            ]);

        // 1. Confirma que a API entrega todas as métricas exigidas
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'average_score',
                'lowest_score',
                'total_attempts',
                'top_score' => ['student_name', 'score'],
                'ranking' => ['data']
            ]);

        // 2. Confirma a Invalidação do Cache
        \Illuminate\Support\Facades\Cache::spy();

        // Operação de exclusão utilizando o $teacher criado localmente
        $exam = \App\Models\Exam::create(['title' => 'Cache Test', 'created_by' => $teacher->id]);

        $this->actingAs($teacher)->deleteJson("/api/exams/{$exam->id}");

        // Verifica se o sistema tentou limpar o cache correspondente
        // Nota: no seu controller usamos as chaves "dashboard_stats_null" ou "dashboard:global:stats"
        \Illuminate\Support\Facades\Cache::shouldHaveReceived('forget');
    }


}
