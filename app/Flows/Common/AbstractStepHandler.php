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

    protected function normalizedText(array $input): string
    {
        return mb_strtolower(trim((string) ($input['text'] ?? '')));
    }

    protected function isCancelCommand(array $input): bool
    {
        return $this->matchesConfiguredKeyword($input, 'medicina_laboral.conversation.cancel_keywords');
    }

    protected function isRestartCommand(array $input): bool
    {
        return $this->matchesConfiguredKeyword($input, 'medicina_laboral.conversation.allowed_restart_keywords');
    }

    protected function buildNumberedOptionsMessage(string $headerKey, array $options): string
    {
        $lines = [__($headerKey)];

        foreach (array_values($options) as $index => $label) {
            $lines[] = ($index + 1) . '. ' . $label;
        }

        return implode("\n", $lines);
    }

    private function matchesConfiguredKeyword(array $input, string $configKey): bool
    {
        $text = $this->normalizedText($input);

        if ($text === '') {
            return false;
        }

        $keywords = array_map(
            static fn (string $value): string => mb_strtolower(trim($value)),
            config($configKey, [])
        );

        return in_array($text, $keywords, true);
    }
}
