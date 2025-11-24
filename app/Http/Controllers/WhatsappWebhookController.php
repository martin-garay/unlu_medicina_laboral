<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Conversacion;
use App\Services\WhatsAppSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(private readonly WhatsAppSender $whatsAppSender)
    {
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

        if (!$from) {
            return response()->json(['status' => 'no_sender'], 200);
        }

        $conversation = Conversacion::firstOrCreate(
            ['wa_number' => $from],
            ['estado' => 'esperando_dni']
        );

        $menuConfig = config('whatsapp_menu');
        $responseText = null;
        $shouldSendMenu = false;

        switch ($conversation->estado) {
            case 'esperando_dni':
                $conversation->dni = $text;
                $conversation->estado = 'esperando_tipo';
                $conversation->save();
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
                    $conversation->estado = 'esperando_cantidad_dias';
                    $conversation->save();
                    $responseText = '¿Cuántos días de inasistencia querés registrar?';
                } elseif ($selectedTipo === 'certificado') {
                    $conversation->tipo = 'certificado';
                    $conversation->estado = 'esperando_certificado';
                    $conversation->save();
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

        if ($shouldSendMenu) {
            $this->whatsAppSender->sendInteractiveMenu($from, $menuConfig);
        } elseif ($responseText) {
            $this->whatsAppSender->sendText($from, $responseText);
        }

        return response()->json(['status' => 'ok']);
    }
}
