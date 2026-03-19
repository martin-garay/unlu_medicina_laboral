<?php

namespace App\Services\Notifications\Contracts;

use App\Models\Aviso;

interface BusinessNotificationSender
{
    public function sendAvisoRegistered(Aviso $aviso): void;
}
