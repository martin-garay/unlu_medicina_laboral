<?php

namespace Tests\Feature\Console;

use App\Services\ConversationTimeoutService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Mockery;
use Tests\TestCase;

class ProcessConversationTimeoutsCommandTest extends TestCase
{
    public function test_command_processes_timeouts_with_explicit_now_option(): void
    {
        $service = Mockery::mock(ConversationTimeoutService::class);
        $this->app->instance(ConversationTimeoutService::class, $service);

        $service->shouldReceive('process')
            ->once()
            ->withArgs(function (?CarbonInterface $now): bool {
                return $now?->equalTo(Carbon::parse('2026-03-20 10:15:00'));
            })
            ->andReturn([
                'checked' => 4,
                'eligible' => 3,
                'warning_1_sent' => 1,
                'cancelled' => 1,
                'second_threshold_action' => 'cancel',
            ]);

        $this->artisan('conversations:process-timeouts', [
            '--now' => '2026-03-20 10:15:00',
        ])
            ->expectsOutput('Conversaciones revisadas: 4 | Elegibles: 3 | Recordatorios enviados: 1 | Canceladas: 1')
            ->assertSuccessful();
    }

    public function test_command_fails_when_now_option_is_invalid(): void
    {
        $service = Mockery::mock(ConversationTimeoutService::class);
        $this->app->instance(ConversationTimeoutService::class, $service);

        $service->shouldNotReceive('process');

        $this->artisan('conversations:process-timeouts', [
            '--now' => 'not-a-date',
        ])
            ->expectsOutput('La opción --now debe ser una fecha/hora válida.')
            ->assertFailed();
    }
}
