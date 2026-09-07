<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoggingConfigurationTest extends TestCase
{
    public function test_file_log_channels_are_group_writable(): void
    {
        $expectedMode = 0664;

        $this->assertSame($expectedMode, config('logging.channels.single.permission'));
        $this->assertSame($expectedMode, config('logging.channels.daily.permission'));
        $this->assertSame($expectedMode, config('logging.channels.emergency.permission'));
    }
}
