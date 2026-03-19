<?php

namespace App\Services;

use App\Models\Conversacion;
use App\Flows\Common\MessageResolver;
use Carbon\CarbonInterface;

class ConversationTimeoutService
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
        private readonly ConversationMessageService $conversationMessageService,
        private readonly ConversationEventService $conversationEventService,
        private readonly WhatsAppSender $whatsAppSender,
        private readonly MessageResolver $messageResolver,
    ) {
    }

    public function process(?CarbonInterface $now = null): array
    {
        $now ??= now();

        $summary = [
            'checked' => 0,
            'warning_1_sent' => 0,
            'cancelled' => 0,
        ];

        Conversacion::query()
            ->active()
            ->orderBy('id')
            ->get()
            ->each(function (Conversacion $conversation) use ($now, &$summary): void {
                $summary['checked']++;

                if (!$this->isEligibleForTimeout($conversation, $now)) {
                    return;
                }

                if ($this->shouldCancelByInactivity($conversation, $now)) {
                    $this->cancelByInactivity($conversation, $now);
                    $summary['cancelled']++;

                    return;
                }

                if ($this->shouldSendFirstWarning($conversation, $now)) {
                    $this->sendFirstWarning($conversation, $now);
                    $summary['warning_1_sent']++;
                }
            });

        return $summary;
    }

    private function isEligibleForTimeout(Conversacion $conversation, CarbonInterface $now): bool
    {
        if (!$conversation->isActive()) {
            return false;
        }

        if ($conversation->wa_number === null || $conversation->wa_number === '') {
            return false;
        }

        if ($conversation->finalizada_en !== null) {
            return false;
        }

        return $this->referenceTimestamp($conversation, $now) !== null;
    }

    private function shouldSendFirstWarning(Conversacion $conversation, CarbonInterface $now): bool
    {
        if ($conversation->primer_umbral_notificado_en !== null) {
            return false;
        }

        $reference = $this->referenceTimestamp($conversation, $now);

        if ($reference === null) {
            return false;
        }

        return $reference->lte($now->copy()->subMinutes($this->firstThresholdMinutes()));
    }

    private function shouldCancelByInactivity(Conversacion $conversation, CarbonInterface $now): bool
    {
        if ($conversation->segundo_umbral_notificado_en !== null) {
            return false;
        }

        $reference = $this->referenceTimestamp($conversation, $now);

        if ($reference === null) {
            return false;
        }

        return $reference->lte($now->copy()->subMinutes($this->secondThresholdMinutes()));
    }

    private function sendFirstWarning(Conversacion $conversation, CarbonInterface $now): void
    {
        $template = config('medicina_laboral.mensajes.templates.inactividad_recordatorio');
        $message = $this->messageResolver->resolveTemplate($template, $this->timeoutTemplateData($conversation));

        $this->whatsAppSender->sendText($conversation->wa_number, $message);

        $this->conversationMessageService->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => 'text',
            'contenido_texto' => $message,
            'template_name' => $template,
            'step_key' => $conversation->currentStepKey(),
            'metadata' => [
                'automated' => true,
                'timeout_stage' => 'warning_1',
            ],
            'created_at' => $now,
        ]);

        $conversation = $this->conversationManager->markFirstThresholdNotified($conversation, $now);

        $this->conversationEventService->record($conversation, 'timeout_warning_1', [
            'descripcion' => 'Primer recordatorio automático por inactividad',
            'codigo' => 'timeout_warning_1',
            'metadata' => $this->timeoutMetadata($conversation, $now),
        ]);
    }

    private function cancelByInactivity(Conversacion $conversation, CarbonInterface $now): void
    {
        $template = config('medicina_laboral.mensajes.templates.inactividad_cancelacion');
        $message = $this->messageResolver->resolveTemplate($template, $this->timeoutTemplateData($conversation));

        $this->whatsAppSender->sendText($conversation->wa_number, $message);

        $this->conversationMessageService->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => 'text',
            'contenido_texto' => $message,
            'template_name' => $template,
            'step_key' => $conversation->currentStepKey(),
            'metadata' => [
                'automated' => true,
                'timeout_stage' => 'warning_2',
            ],
            'created_at' => $now,
        ]);

        $conversation = $this->conversationManager->markSecondThresholdNotified($conversation, $now);

        $this->conversationEventService->record($conversation, 'timeout_warning_2', [
            'descripcion' => 'Segundo umbral de inactividad alcanzado',
            'codigo' => 'timeout_warning_2',
            'metadata' => $this->timeoutMetadata($conversation, $now),
        ]);

        $conversation = $this->conversationManager->closeConversation(
            $conversation,
            'inactivity_timeout',
            [
                'estado' => 'cancelada',
                'estado_actual' => 'cancelada',
                'paso_actual' => 'cancelada',
            ],
            $now,
        );

        $this->conversationEventService->record($conversation, 'conversation_cancelled_by_inactivity', [
            'descripcion' => 'Conversación cancelada automáticamente por inactividad',
            'codigo' => 'inactivity_timeout',
            'metadata' => $this->timeoutMetadata($conversation, $now),
        ]);

        $this->conversationEventService->recordConversationClosed($conversation, 'inactivity_timeout', [
            'automatic' => true,
            'trigger' => 'scheduler',
        ]);
    }

    private function timeoutMetadata(Conversacion $conversation, CarbonInterface $now): array
    {
        return [
            'wa_number' => $conversation->wa_number,
            'step_key' => $conversation->currentStepKey(),
            'evaluated_at' => $now->toIso8601String(),
            'first_threshold_minutes' => $this->firstThresholdMinutes(),
            'second_threshold_minutes' => $this->secondThresholdMinutes(),
        ];
    }

    private function timeoutTemplateData(Conversacion $conversation): array
    {
        return [
            'flow_label' => $this->flowLabel($conversation),
            'first_threshold_minutes' => $this->firstThresholdMinutes(),
            'second_threshold_minutes' => $this->secondThresholdMinutes(),
        ];
    }

    private function referenceTimestamp(Conversacion $conversation, CarbonInterface $now): ?CarbonInterface
    {
        return $conversation->ultimo_mensaje_recibido_en
            ?? $conversation->created_at
            ?? $now;
    }

    private function firstThresholdMinutes(): int
    {
        return (int) config('medicina_laboral.conversation.first_inactivity_minutes', 30);
    }

    private function secondThresholdMinutes(): int
    {
        $second = (int) config('medicina_laboral.conversation.second_inactivity_minutes', 60);

        return max($second, $this->firstThresholdMinutes());
    }

    private function flowLabel(Conversacion $conversation): string
    {
        return match ($conversation->tipo_flujo) {
            'inasistencia' => 'aviso de ausencia',
            'certificado' => 'anticipo de certificado médico',
            default => 'gestión actual',
        };
    }
}
