<?php

namespace App\Flows\Common;

use App\Flows\Common\Contracts\StepHandler;
use App\Models\Conversacion;

abstract class AbstractStepHandler implements StepHandler
{
    public function supports(Conversacion $conversation): bool
    {
        return $conversation->currentStepKey() === $this->stepKey();
    }

    protected function success(?string $messageKey = null, array $attributes = []): StepResult
    {
        return StepResult::make($messageKey, $attributes);
    }

    protected function invalid(string $errorCode, ?string $messageKey = null, array $attributes = []): StepResult
    {
        return StepResult::invalid($errorCode, $messageKey, $attributes);
    }
}
