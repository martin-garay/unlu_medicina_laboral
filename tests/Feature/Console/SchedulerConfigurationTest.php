<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerConfigurationTest extends TestCase
{
    public function test_scheduler_registers_timeout_processing_every_minute_without_overlapping(): void
    {
        $schedule = $this->app->make(Schedule::class);

        $event = collect($schedule->events())
            ->first(fn ($event) => str_contains($event->command, 'conversations:process-timeouts'));

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(10, $event->expiresAt);
        $this->assertSame('conversations:process-timeouts', $event->description);
    }
}
