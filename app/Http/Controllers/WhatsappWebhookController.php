<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Conversacion;
use App\Services\ConversationEventService;
use App\Services\ConversationManager;
use App\Services\ConversationMessageService;
use App\Services\WhatsAppSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppSender $whatsAppSender,
        private readonly ConversationManager $conversationManager,
        private readonly ConversationMessageService $conversationMessageService,
        private readonly ConversationEventService $conversationEventService,
    ) {
    }

    /**
     * Verificación de webhook (GET).
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $verifyToken = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $verifyToken && $mode === 'subscribe' && $verifyToken === env('WHATSAPP_VERIFY_TOKEN')) {
            return response($challenge, 200);
        }

        return response('Error: token inválido.', 403);
    }

    /**
     * Recepción de mensajes entrantes (POST).
     */
    public function receive(Request $request)
    {
        Log::info('Webhook payload', $request->all());

        $entry = $request->input('entry.0.changes.0.value.messages.0');
        if (!$entry) {
            return response()->json(['status' => 'no_message'], 200);
        }

        $from = $entry['from'] ?? null;
        $text = $entry['text']['body'] ?? '';
        $buttonId = $entry['interactive']['button_reply']['id'] ?? null;
        $providerMessageId = $entry['id'] ?? null;
        $incomingMessageType = $this->resolveIncomingMessageType($entry);

        if (!$from) {
            return response()->json(['status' => 'no_sender'], 200);
        }

        $conversation = $this->conversationManager->findActiveByWaNumber($from);

        if (!$conversation) {
            $conversation = $this->conversationManager->createConversation($from, [
                'estado' => 'esperando_dni',
                'estado_actual' => 'esperando_dni',
                'paso_actual' => 'esperando_dni',
            ]);

            $this->conversationEventService->record($conversation, 'conversation_started', [
                'step_key' => $conversation->paso_actual,
                'descripcion' => 'Conversacion iniciada desde webhook',
                'metadata' => [
                    'wa_number' => $from,
                    'provider_message_id' => $providerMessageId,
                ],
            ]);
        }

        $this->conversationMessageService->registerIncomingMessage($conversation, [
            'provider_message_id' => $providerMessageId,
            'tipo_mensaje' => $incomingMessageType,
            'step_key' => $conversation->paso_actual ?? $conversation->estado,
            'contenido_texto' => $text,
            'payload_crudo' => $entry,
            'metadata' => [
                'button_id' => $buttonId,
                'from' => $from,
            ],
        ]);

        $this->conversationEventService->record($conversation, 'incoming_message_received', [
            'step_key' => $conversation->paso_actual ?? $conversation->estado,
            'descripcion' => 'Mensaje entrante recibido',
            'metadata' => [
                'provider_message_id' => $providerMessageId,
                'tipo_mensaje' => $incomingMessageType,
                'button_id' => $buttonId,
            ],
        ]);

        $menuConfig = config('whatsapp_menu');
        $responseText = null;
        $shouldSendMenu = false;

        switch ($conversation->estado) {
            case 'esperando_dni':
                $conversation->dni = $text;
                $this->transitionConversation($conversation, 'esperando_tipo');
                $shouldSendMenu = true;
                break;

            case 'esperando_tipo':
                $selectedTipo = null;

                if ($buttonId && isset($menuConfig['id_to_tipo'][$buttonId])) {
                    $selectedTipo = $menuConfig['id_to_tipo'][$buttonId];
                } else {
                    $normalizedText = strtolower(trim($text));
                    if (isset($menuConfig['text_to_tipo'][$normalizedText])) {
                        $selectedTipo = $menuConfig['text_to_tipo'][$normalizedText];
                    }
                }

                if ($selectedTipo === 'inasistencia') {
                    $conversation->tipo = 'inasistencia';
                    $conversation->tipo_flujo = 'inasistencia';
                    $this->transitionConversation($conversation, 'esperando_cantidad_dias');
                    $responseText = '¿Cuántos días de inasistencia querés registrar?';
                } elseif ($selectedTipo === 'certificado') {
                    $conversation->tipo = 'certificado';
                    $conversation->tipo_flujo = 'certificado';
                    $this->transitionConversation($conversation, 'esperando_certificado');
                    $responseText = 'Podés escribir un breve detalle del certificado o adjuntar una imagen (por ahora solo manejamos texto).';
                } else {
                    $shouldSendMenu = true;
                }
                break;

            case 'esperando_cantidad_dias':
                $cantidadDias = (int) $text;
                Aviso::create([
                    'dni' => $conversation->dni,
                    'tipo' => 'inasistencia',
                    'fecha_inicio' => now()->toDateString(),
                    'cantidad_dias' => $cantidadDias,
                    'wa_number' => $conversation->wa_number,
                ]);
                $conversation = $this->conversationManager->closeConversation($conversation, 'completed', [
                    'estado' => 'completada',
                    'estado_actual' => 'completada',
                    'paso_actual' => 'completada',
                ]);
                $responseText = '✅ Inasistencia registrada. ¡Que te mejores!';
                break;

            case 'esperando_certificado':
                Aviso::create([
                    'dni' => $conversation->dni,
                    'tipo' => 'certificado',
                    'certificado_base64' => $text,
                    'wa_number' => $conversation->wa_number,
                ]);
                $conversation = $this->conversationManager->closeConversation($conversation, 'completed', [
                    'estado' => 'completada',
                    'estado_actual' => 'completada',
                    'paso_actual' => 'completada',
                ]);
                $responseText = '✅ Certificado registrado. ¡Gracias por avisar!';
                break;

            default:
                $conversation = $this->conversationManager->closeConversation($conversation, 'unexpected_state', [
                    'estado' => 'cancelada',
                    'estado_actual' => 'cancelada',
                    'paso_actual' => 'cancelada',
                ]);

                $this->conversationEventService->recordConversationClosed($conversation, 'unexpected_state', [
                    'legacy_estado' => $conversation->estado,
                ]);
                $responseText = 'Por favor, escribí tu DNI para comenzar.';
                break;
        }

        if ($shouldSendMenu) {
            $this->sendMenuResponse($conversation, $from, $menuConfig);
        } elseif ($responseText) {
            $this->sendTextResponse($conversation, $from, $responseText);
        }

        return response()->json(['status' => 'ok']);
    }

    private function transitionConversation(Conversacion $conversation, string $newState): Conversacion
    {
        $previousState = $conversation->estado;

        $conversation->forceFill([
            'estado' => $newState,
            'estado_actual' => $newState,
            'paso_actual' => $newState,
        ])->save();

        $conversation = $conversation->refresh();

        $this->conversationEventService->recordStateChange(
            $conversation,
            (string) $previousState,
            $newState
        );

        return $conversation;
    }

    private function sendTextResponse(Conversacion $conversation, string $to, string $message): void
    {
        $this->whatsAppSender->sendText($to, $message);

        $this->conversationMessageService->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => 'text',
            'step_key' => $conversation->paso_actual ?? $conversation->estado,
            'contenido_texto' => $message,
            'payload_crudo' => [
                'type' => 'text',
                'text' => ['body' => $message],
            ],
            'metadata' => [
                'transport' => 'whatsapp_cloud_api',
            ],
        ]);

        $this->conversationEventService->record($conversation, 'outgoing_message_sent', [
            'step_key' => $conversation->paso_actual ?? $conversation->estado,
            'descripcion' => 'Mensaje saliente enviado',
            'metadata' => [
                'tipo_mensaje' => 'text',
            ],
        ]);
    }

    private function sendMenuResponse(Conversacion $conversation, string $to, array $menuConfig): void
    {
        $this->whatsAppSender->sendInteractiveMenu($to, $menuConfig);

        $this->conversationMessageService->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => 'interactive',
            'step_key' => $conversation->paso_actual ?? $conversation->estado,
            'contenido_texto' => $menuConfig['body_text'] ?? null,
            'payload_crudo' => [
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => ['text' => $menuConfig['body_text'] ?? ''],
                    'buttons' => $menuConfig['buttons'] ?? [],
                ],
            ],
            'metadata' => [
                'transport' => 'whatsapp_cloud_api',
            ],
        ]);

        $this->conversationEventService->record($conversation, 'outgoing_message_sent', [
            'step_key' => $conversation->paso_actual ?? $conversation->estado,
            'descripcion' => 'Menu interactivo enviado',
            'metadata' => [
                'tipo_mensaje' => 'interactive',
            ],
        ]);
    }

    private function resolveIncomingMessageType(array $entry): string
    {
        if (isset($entry['interactive']['button_reply'])) {
            return 'button';
        }

        if (isset($entry['interactive'])) {
            return 'interactive';
        }

        if (isset($entry['text'])) {
            return 'text';
        }

        return 'unknown';
    }
}
