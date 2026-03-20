<?php

namespace App\Mail;

use App\Models\Aviso;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AvisoRegisteredMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Aviso $aviso,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject((string) config('medicina_laboral.mail.aviso_registered_subject', 'Aviso de ausencia registrado'))
            ->text('emails.aviso_registered');
    }
}
