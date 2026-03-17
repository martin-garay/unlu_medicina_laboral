<?php

namespace App\Flows\Common\Contracts;

use App\Flows\Common\StepResult;
use App\Models\Conversacion;

interface StepHandler
{
    public function supports(Conversacion $conversation): bool;

    public function stepKey(): string;

    public function handle(Conversacion $conversation, array $input = []): StepResult;
}
