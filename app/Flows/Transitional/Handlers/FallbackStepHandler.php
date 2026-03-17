<?php

namespace App\Flows\Transitional\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;

class FallbackStepHandler extends AbstractStepHandler
{
    public function stepKey(): string
    {
        return '__fallback__';
    }

    public function supports(Conversacion $conversation): bool
    {
        return true;
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        return $this->invalid('unsupported_step', 'whatsapp.general.reinicio', [
            'should_cancel' => true,
        ]);
    }
}
