<?php

namespace Tests\Unit\Services\Notifications;

use App\Mail\AvisoRegisteredMail;
use App\Models\Aviso;
use App\Services\Notifications\LaravelMailBusinessNotificationSender;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LaravelMailBusinessNotificationSenderTest extends TestCase
{
    public function test_sends_aviso_registered_mail_when_recipient_is_configured(): void
    {
        Mail::fake();
        config()->set('medicina_laboral.mail.aviso_registered_recipient', 'rrhh@example.test');

        $sender = new LaravelMailBusinessNotificationSender(app('mail.manager'));
        $sender->sendAvisoRegistered(new Aviso([
            'id' => 15,
            'nombre_completo' => 'Ana Perez',
            'legajo' => '10001',
        ]));

        Mail::assertSent(AvisoRegisteredMail::class);
    }

    public function test_does_not_send_mail_when_recipient_is_missing(): void
    {
        Mail::fake();
        config()->set('medicina_laboral.mail.aviso_registered_recipient', null);

        $sender = new LaravelMailBusinessNotificationSender(app('mail.manager'));
        $sender->sendAvisoRegistered(new Aviso([
            'id' => 15,
        ]));

        Mail::assertNothingSent();
    }
}
