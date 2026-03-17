<?php

namespace App\Flows\Common;

class StepResult
{
    public function __construct(
        public readonly bool $isValid = true,
        public readonly ?string $message = null,
        public readonly ?string $messageKey = null,
        public readonly array $messageParams = [],
        public readonly ?string $template = null,
        public readonly array $templateData = [],
        public readonly ?string $nextStep = null,
        public readonly ?string $nextState = null,
        public readonly ?string $errorCode = null,
        public readonly int $incrementAttempts = 0,
        public readonly bool $shouldShowMenu = false,
        public readonly bool $shouldCancel = false,
        public readonly bool $shouldFinish = false,
        public readonly array $payload = [],
    ) {
    }

    public static function make(
        ?string $messageKey = null,
        array $attributes = [],
    ): self {
        return new self(
            isValid: $attributes['is_valid'] ?? true,
            message: $attributes['message'] ?? null,
            messageKey: $messageKey ?? ($attributes['message_key'] ?? null),
            messageParams: $attributes['message_params'] ?? [],
            template: $attributes['template'] ?? null,
            templateData: $attributes['template_data'] ?? [],
            nextStep: $attributes['next_step'] ?? null,
            nextState: $attributes['next_state'] ?? null,
            errorCode: $attributes['error_code'] ?? null,
            incrementAttempts: $attributes['increment_attempts'] ?? 0,
            shouldShowMenu: $attributes['should_show_menu'] ?? false,
            shouldCancel: $attributes['should_cancel'] ?? false,
            shouldFinish: $attributes['should_finish'] ?? false,
            payload: $attributes['payload'] ?? [],
        );
    }

    public static function invalid(string $errorCode, ?string $messageKey = null, array $attributes = []): self
    {
        return self::make($messageKey, array_merge($attributes, [
            'is_valid' => false,
            'error_code' => $errorCode,
        ]));
    }

    public function hasMessage(): bool
    {
        return $this->message !== null || $this->messageKey !== null || $this->template !== null;
    }

    public function hasNextState(): bool
    {
        return $this->nextState !== null;
    }

    public function closesConversation(): bool
    {
        return $this->shouldCancel || $this->shouldFinish;
    }
}
