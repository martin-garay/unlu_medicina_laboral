<?php

namespace App\Services;

use App\Models\Conversacion;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ConversationManager
{
    public function findActiveByWaNumber(string $waNumber): ?Conversacion
    {
        return $this->findActiveByParticipant(Conversacion::CANAL_WHATSAPP, $waNumber);
    }

    public function findActiveByParticipant(string $channel, string $participantId): ?Conversacion
    {
        return Conversacion::query()
            ->active()
            ->where('canal', $channel)
            ->where('wa_number', $participantId)
            ->latest('id')
            ->first();
    }

    public function createConversation(string $waNumber, array $attributes = []): Conversacion
    {
        return $this->createConversationForChannel(Conversacion::CANAL_WHATSAPP, $waNumber, $attributes);
    }

    public function createConversationForChannel(
        string $channel,
        string $participantId,
        array $attributes = [],
    ): Conversacion {
        return Conversacion::create(array_merge(
            $this->defaultConversationAttributes($participantId, $channel),
            Arr::except($attributes, ['wa_number'])
        ));
    }

    public function getOrCreateActiveConversation(string $waNumber, array $attributes = []): Conversacion
    {
        return $this->getOrCreateActiveConversationForChannel(
            Conversacion::CANAL_WHATSAPP,
            $waNumber,
            $attributes
        );
    }

    public function getOrCreateActiveConversationForChannel(
        string $channel,
        string $participantId,
        array $attributes = [],
    ): Conversacion {
        $conversation = $this->findActiveByParticipant($channel, $participantId);

        if ($conversation) {
            return $conversation;
        }

        return $this->createConversationForChannel($channel, $participantId, $attributes);
    }

    public function touchIncomingActivity(Conversacion $conversation, CarbonInterface|string|null $timestamp = null): Conversacion
    {
        $conversation->forceFill([
            'ultimo_mensaje_recibido_en' => $timestamp ?? now(),
        ])->save();

        return $conversation->refresh();
    }

    public function transitionConversation(
        Conversacion $conversation,
        string $newState,
        array $attributes = [],
    ): Conversacion {
        $conversation->forceFill(array_merge([
            'estado' => $newState,
            'estado_actual' => $newState,
            'paso_actual' => $newState,
            'cantidad_intentos_actual' => 0,
        ], $attributes))->save();

        return $conversation->refresh();
    }

    public function touchOutgoingActivity(Conversacion $conversation, CarbonInterface|string|null $timestamp = null): Conversacion
    {
        $conversation->forceFill([
            'ultimo_mensaje_enviado_en' => $timestamp ?? now(),
        ])->save();

        return $conversation->refresh();
    }

    public function incrementIncomingCounters(
        Conversacion $conversation,
        ?bool $isValid = null,
        int $attemptIncrement = 0,
        CarbonInterface|string|null $timestamp = null,
    ): Conversacion {
        $conversation->forceFill([
            'cantidad_mensajes_recibidos' => $conversation->cantidad_mensajes_recibidos + 1,
            'cantidad_mensajes_validos' => $conversation->cantidad_mensajes_validos + ($isValid === true ? 1 : 0),
            'cantidad_mensajes_invalidos' => $conversation->cantidad_mensajes_invalidos + ($isValid === false ? 1 : 0),
            'cantidad_intentos_actual' => $conversation->cantidad_intentos_actual + max(0, $attemptIncrement),
            'cantidad_intentos_totales' => $conversation->cantidad_intentos_totales + max(0, $attemptIncrement),
        ])->save();

        return $this->touchIncomingActivity($conversation, $timestamp);
    }

    public function incrementOutgoingCounters(
        Conversacion $conversation,
        CarbonInterface|string|null $timestamp = null,
    ): Conversacion {
        $conversation->increment('cantidad_mensajes_enviados');

        return $this->touchOutgoingActivity($conversation, $timestamp);
    }

    public function resetCurrentAttempts(Conversacion $conversation): Conversacion
    {
        $conversation->forceFill([
            'cantidad_intentos_actual' => 0,
        ])->save();

        return $conversation->refresh();
    }

    public function markFirstThresholdNotified(
        Conversacion $conversation,
        CarbonInterface|string|null $timestamp = null,
    ): Conversacion {
        $conversation->forceFill([
            'primer_umbral_notificado_en' => $timestamp ?? now(),
        ])->save();

        return $conversation->refresh();
    }

    public function markSecondThresholdNotified(
        Conversacion $conversation,
        CarbonInterface|string|null $timestamp = null,
    ): Conversacion {
        $conversation->forceFill([
            'segundo_umbral_notificado_en' => $timestamp ?? now(),
        ])->save();

        return $conversation->refresh();
    }

    public function closeConversation(
        Conversacion $conversation,
        string $reason,
        array $attributes = [],
        CarbonInterface|string|null $timestamp = null,
    ): Conversacion {
        $closedAt = $timestamp ?? now();

        $conversation->forceFill(array_merge([
            'activa' => false,
            'finalizada_en' => $closedAt,
            'motivo_finalizacion' => $reason,
            'estado_actual' => $attributes['estado_actual'] ?? 'finalizada',
        ], Arr::except($attributes, ['motivo_finalizacion'])))->save();

        return $conversation->refresh();
    }

    private function defaultConversationAttributes(string $participantId, string $channel): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'wa_number' => $participantId,
            'canal' => $channel,
            'estado_actual' => 'menu_principal',
            'paso_actual' => 'menu_principal',
            'activa' => true,
            'metadata' => [],
            'estado' => 'menu_principal',
        ];
    }
}
