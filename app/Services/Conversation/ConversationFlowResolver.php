<?php

namespace App\Services\Conversation;

use App\Flows\Common\Contracts\StepHandler;
use App\Models\Conversacion;

class ConversationFlowResolver
{
    /**
     * @param iterable<StepHandler> $handlers
     */
    public function __construct(
        private readonly iterable $handlers,
    ) {
    }

    public function resolve(Conversacion $conversation): StepHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($conversation)) {
                return $handler;
            }
        }

        throw new \RuntimeException(sprintf(
            'No se encontro un handler para el paso [%s].',
            $conversation->currentStepKey() ?? 'null'
        ));
    }
}
