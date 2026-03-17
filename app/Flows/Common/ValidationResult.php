<?php

namespace App\Flows\Common;

class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly ?string $errorCode = null,
        public readonly array $normalized = [],
    ) {
    }

    public static function valid(array $normalized = []): self
    {
        return new self(true, null, $normalized);
    }

    public static function invalid(string $errorCode, array $normalized = []): self
    {
        return new self(false, $errorCode, $normalized);
    }
}
