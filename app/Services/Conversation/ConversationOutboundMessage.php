<?php

namespace App\Services\Conversation;

class ConversationOutboundMessage
{
    private function __construct(
        public readonly string $type,
        public readonly ?string $text = null,
        public readonly array $menuConfig = [],
    ) {
    }

    public static function text(string $text): self
    {
        return new self(type: 'text', text: $text);
    }

    public static function menu(array $menuConfig): self
    {
        return new self(type: 'menu', menuConfig: $menuConfig);
    }

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isMenu(): bool
    {
        return $this->type === 'menu';
    }

    public function traceMessageType(): string
    {
        return $this->isMenu() ? 'interactive' : 'text';
    }

    public function traceContent(): ?string
    {
        return $this->isMenu()
            ? ($this->menuConfig['body_text'] ?? null)
            : $this->text;
    }

    public function tracePayload(): array
    {
        if ($this->isMenu()) {
            return [
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => ['text' => $this->menuConfig['body_text'] ?? ''],
                    'buttons' => $this->menuConfig['buttons'] ?? [],
                ],
            ];
        }

        return [
            'type' => 'text',
            'text' => ['body' => $this->text ?? ''],
        ];
    }
}
