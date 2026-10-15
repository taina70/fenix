<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

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
}
