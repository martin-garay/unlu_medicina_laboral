<?php

namespace App\Services\Notifications;

use App\Mail\AvisoRegisteredMail;
use App\Models\Aviso;
use App\Services\Notifications\Contracts\BusinessNotificationSender;
use Illuminate\Contracts\Mail\Factory as MailFactory;

class LaravelMailBusinessNotificationSender implements BusinessNotificationSender
{
    public function __construct(
        private readonly MailFactory $mail,
    ) {
    }

    public function sendAvisoRegistered(Aviso $aviso): void
    {
        $recipient = (string) config('medicina_laboral.mail.aviso_registered_recipient', '');

        if ($recipient === '') {
            return;
        }

        $this->mail->to($recipient)->send(new AvisoRegisteredMail($aviso));
    }
}
