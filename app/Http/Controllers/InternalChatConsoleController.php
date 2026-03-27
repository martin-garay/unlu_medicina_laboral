<?php

namespace App\Http\Controllers;

use App\Models\Conversacion;
use App\Services\Conversation\ConversationInboundMessage;
use App\Services\Conversation\ConversationInteractionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InternalChatConsoleController extends Controller
{
    private const SESSION_PARTICIPANT_ID = 'internal_chat_console.participant_id';
    private const SESSION_TRANSCRIPT = 'internal_chat_console.transcript';

    public function __construct(
        private readonly ConversationInteractionService $conversationInteractionService,
    ) {
    }

    public function index(Request $request): View
    {
        $participantId = $this->participantId($request);
        $transcript = $request->session()->get(self::SESSION_TRANSCRIPT, []);

        return view('internal_chat.console', [
            'participantId' => $participantId,
            'transcript' => $transcript,
            'activeMenu' => $this->activeMenu($transcript),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'text' => ['nullable', 'string'],
            'button_id' => ['nullable', 'string'],
            'button_title' => ['nullable', 'string'],
        ]);

        $text = $validated['text'] ?? null;
        $buttonId = $validated['button_id'] ?? null;

        if ($text === null && $buttonId === null) {
            return back()->withErrors([
                'text' => __('internal_chat.validation.message_required'),
            ]);
        }

        $participantId = $this->participantId($request);
        $transcript = $request->session()->get(self::SESSION_TRANSCRIPT, []);
        $transcript[] = $this->buildUserTranscriptEntry($text, $buttonId, $validated['button_title'] ?? null);

        $interaction = $this->conversationInteractionService->handleInboundMessage(
            new ConversationInboundMessage(
                channel: Conversacion::CANAL_INTERNO,
                participantId: $participantId,
                text: $text,
                buttonId: $buttonId,
                providerMessageId: null,
                incomingMessageType: $buttonId !== null ? 'button' : 'text',
                rawPayload: $validated,
                content: $text ?? $buttonId,
            )
        );

        foreach ($interaction->outboundMessages as $message) {
            $transcript[] = [
                'actor' => 'bot',
                'type' => $message->type,
                'text' => $message->text,
                'menu' => $message->menuConfig,
            ];
        }

        $request->session()->put(self::SESSION_TRANSCRIPT, $transcript);

        return redirect()->route('internal-chat.console');
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget([
            self::SESSION_PARTICIPANT_ID,
            self::SESSION_TRANSCRIPT,
        ]);

        return redirect()->route('internal-chat.console');
    }

    private function participantId(Request $request): string
    {
        $participantId = $request->session()->get(self::SESSION_PARTICIPANT_ID);

        if (is_string($participantId) && $participantId !== '') {
            return $participantId;
        }

        $participantId = 'console-' . Str::lower((string) Str::uuid());
        $request->session()->put(self::SESSION_PARTICIPANT_ID, $participantId);

        return $participantId;
    }

    private function activeMenu(array $transcript): ?array
    {
        $lastEntry = end($transcript);

        if (! is_array($lastEntry) || ($lastEntry['type'] ?? null) !== 'menu') {
            return null;
        }

        return $lastEntry['menu'] ?? null;
    }

    private function buildUserTranscriptEntry(?string $text, ?string $buttonId, ?string $buttonTitle): array
    {
        if ($buttonId !== null) {
            return [
                'actor' => 'user',
                'type' => 'button',
                'text' => $buttonTitle ?? $buttonId,
                'button_id' => $buttonId,
            ];
        }

        return [
            'actor' => 'user',
            'type' => 'text',
            'text' => $text,
        ];
    }
}
