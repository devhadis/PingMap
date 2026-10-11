<?php

namespace Tests\Feature;

use App\Jobs\TestRedisConnection;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TestRedisConnectionTest extends TestCase
{
    public function test_job_registra_mensagem_no_log(): void
    {
        Log::spy();

        $job = new TestRedisConnection();
        $job->handle();

        Log::shouldHaveReceived('info')
            ->once()
            ->with('PingMap: Worker Redis funcionando!');
    }
}
