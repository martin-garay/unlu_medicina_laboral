<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Conversacion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
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

        if (!$from) {
            return response()->json(['status' => 'no_sender'], 200);
        }

        $conversation = Conversacion::firstOrCreate(
            ['wa_number' => $from],
            ['estado' => 'esperando_dni']
        );

        $responseText = '';

        switch ($conversation->estado) {
            case 'esperando_dni':
                $conversation->dni = $text;
                $conversation->estado = 'esperando_tipo';
                $conversation->save();
                $responseText = '¿Querés notificar una "inasistencia" o subir un "certificado"?';
                break;

            case 'esperando_tipo':
                $lower = strtolower($text);
                if (str_contains($lower, 'inasistencia')) {
                    $conversation->tipo = 'inasistencia';
                    $conversation->estado = 'esperando_cantidad_dias';
                    $conversation->save();
                    $responseText = '¿Cuántos días de inasistencia querés registrar?';
                } elseif (str_contains($lower, 'certificado')) {
                    $conversation->tipo = 'certificado';
                    $conversation->estado = 'esperando_certificado';
                    $conversation->save();
                    $responseText = 'Podés escribir un breve detalle del certificado o adjuntar una imagen (por ahora solo manejamos texto).';
                } else {
                    $responseText = 'No entendí. Escribí "inasistencia" o "certificado".';
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
                $conversation->estado = 'completada';
                $conversation->save();
                $responseText = '✅ Inasistencia registrada. ¡Que te mejores!';
                break;

            case 'esperando_certificado':
                Aviso::create([
                    'dni' => $conversation->dni,
                    'tipo' => 'certificado',
                    'certificado_base64' => $text,
                    'wa_number' => $conversation->wa_number,
                ]);
                $conversation->estado = 'completada';
                $conversation->save();
                $responseText = '✅ Certificado registrado. ¡Gracias por avisar!';
                break;

            default:
                $conversation->delete();
                $responseText = 'Por favor, escribí tu DNI para comenzar.';
                break;
        }

        $this->sendText($from, $responseText);

        return response()->json(['status' => 'ok']);
    }


    private function normalizeToAllowed(string $waId): string
    {
        // Caso Argentina: WhatsApp wa_id viene como 549...
        // Allowed list espera 54... (sin el 9)
        if (str_starts_with($waId, '549')) {
            return '54' . substr($waId, 3);  // quita el 9 post país
        }
        return $waId;
    }


    /**
     * Enviar mensaje de texto a WhatsApp Cloud API.
     */
    private function sendText(string $to, string $message): void
    {
        $token = env('WHATSAPP_TOKEN');
        $phoneId = env('WHATSAPP_PHONE_ID');

        $toAllowed = $this->normalizeToAllowed($to);
        if (!$token || !$phoneId) {
            Log::warning('Faltan credenciales de WhatsApp Cloud API.');
            return;
        }

        $url = "https://graph.facebook.com/v21.0/{$phoneId}/messages";

        Log::debug("Enviando mensaje $message a $to");

        try {
            $resp = Http::withToken($token)
                ->timeout(10)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to'   => $toAllowed,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if ($resp->successful()) {
                Log::info('WA send OK', [
                    'to_raw'     => $to,
                    'to_allowed' => $toAllowed,
                    'status'     => $resp->status(),
                    'body'       => $resp->json(),
                ]);
            } else {
                Log::error('WA send FAILED', [
                    'to_raw'     => $to,
                    'to_allowed' => $toAllowed,
                    'status'     => $resp->status(),
                    'body'       => $resp->json(),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('WA send EXCEPTION', [
                'to_raw'     => $to,
                'to_allowed' => $toAllowed,
                'message'    => $message,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
