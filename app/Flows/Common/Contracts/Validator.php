<?php

namespace App\Flows\Common\Contracts;

use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;

interface Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult;
}
