<?php

namespace Tests\Unit\Services\Notifications;

use App\Models\Aviso;
use App\Services\Notifications\NullBusinessNotificationSender;
use Tests\TestCase;

class NullBusinessNotificationSenderTest extends TestCase
{
    public function test_send_aviso_registered_is_a_safe_no_op(): void
    {
        $sender = new NullBusinessNotificationSender();

        $sender->sendAvisoRegistered(new Aviso());

        $this->assertTrue(true);
    }
}
