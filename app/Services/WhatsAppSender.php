<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppSender
{
    public function __construct(
        private readonly ?string $token = null,
        private readonly ?string $phoneId = null,
    ) {
    }

    public function sendText(string $to, string $message): void
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeToAllowed($to),
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        $this->dispatch($payload, $to, 'text');
    }

    public function sendInteractiveMenu(string $to, array $menuConfig): void
    {
        $buttons = array_map(function (array $button) {
            return [
                'type' => 'reply',
                'reply' => [
                    'id' => $button['id'],
                    'title' => $button['title'],
                ],
            ];
        }, $menuConfig['buttons'] ?? []);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeToAllowed($to),
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $menuConfig['body_text'] ?? ''],
                'action' => ['buttons' => $buttons],
            ],
        ];

        $this->dispatch($payload, $to, 'interactive_menu');
    }

    private function dispatch(array $payload, string $toRaw, string $context): void
    {
        $token = $this->token ?? env('WHATSAPP_TOKEN');
        $phoneId = $this->phoneId ?? env('WHATSAPP_PHONE_ID');

        if (!$token || !$phoneId) {
            Log::warning('Faltan credenciales de WhatsApp Cloud API.');
            return;
        }

        $url = "https://graph.facebook.com/v21.0/{$phoneId}/messages";

        Log::info('Enviando a WhatsApp', [
            'context' => $context,
            'to_raw' => $toRaw,
            'payload' => $payload,
        ]);

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post($url, $payload);

            Log::info('Respuesta de WhatsApp', [
                'context' => $context,
                'to_raw' => $toRaw,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error enviando a WhatsApp', [
                'context' => $context,
                'to_raw' => $toRaw,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeToAllowed(string $waId): string
    {
        if (str_starts_with($waId, '549')) {
            return '54' . substr($waId, 3);
        }

        return $waId;
    }
}
