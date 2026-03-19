<?php

namespace App\Services\Notifications;

use App\Models\Aviso;
use App\Services\Notifications\Contracts\BusinessNotificationSender;

class NullBusinessNotificationSender implements BusinessNotificationSender
{
    public function sendAvisoRegistered(Aviso $aviso): void
    {
    }
}
