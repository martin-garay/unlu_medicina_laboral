<?php

namespace Tests\Feature\Http;

use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class InternalChatConsoleControllerTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
        $compiledPath = sys_get_temp_dir() . '/unlu-medicina-tests-views';

        if (! is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        config()->set('view.compiled', $compiledPath);
    }

    public function test_console_page_renders_successfully(): void
    {
        $this->get('/internal/chat')
            ->assertOk()
            ->assertSee(__('internal_chat.ui.title'))
            ->assertSee(__('internal_chat.ui.empty_state'));
    }

    public function test_console_page_renders_seeded_transcript(): void
    {
        $this->withSession([
            'internal_chat_console.participant_id' => 'console-test-user',
            'internal_chat_console.transcript' => [
                [
                    'actor' => 'user',
                    'type' => 'text',
                    'text' => 'hola',
                ],
                [
                    'actor' => 'bot',
                    'type' => 'text',
                    'text' => trim(view(config('medicina_laboral.mensajes.templates.bienvenida'))->render()),
                ],
                [
                    'actor' => 'bot',
                    'type' => 'menu',
                    'menu' => [
                        'buttons' => [
                            ['id' => 'consultas', 'title' => __('whatsapp.menu.button_titles.consultas')],
                            ['id' => 'op_inasistencia', 'title' => __('whatsapp.menu.button_titles.aviso_ausencia')],
                        ],
                    ],
                ],
            ],
        ])->get('/internal/chat')
            ->assertOk()
            ->assertSee('hola')
            ->assertSee(__('whatsapp.general.bienvenida_institucional'))
            ->assertSee(__('whatsapp.menu.button_titles.aviso_ausencia'));
    }

    public function test_console_post_stores_transcript_and_redirects(): void
    {
        $csrfToken = 'console-csrf-token-1';

        $this->withSession([
            '_token' => $csrfToken,
            'internal_chat_console.participant_id' => 'console-test-user',
        ])->post('/internal/chat', [
            '_token' => $csrfToken,
            'text' => 'hola',
        ])
            ->assertRedirect('/internal/chat')
            ->assertSessionHas('internal_chat_console.transcript', function (array $transcript): bool {
                if (count($transcript) !== 3) {
                    return false;
                }

                return $transcript[0]['text'] === 'hola'
                    && $transcript[1]['type'] === 'text'
                    && $transcript[2]['type'] === 'menu';
            });
    }

    public function test_console_page_advances_flow_with_menu_button_submission(): void
    {
        $csrfToken = 'console-csrf-token-2';

        $this->withSession([
            '_token' => $csrfToken,
            'internal_chat_console.participant_id' => 'console-test-user-2',
        ])->post('/internal/chat', [
            '_token' => $csrfToken,
            'text' => 'hola',
        ])->assertRedirect('/internal/chat');

        $this->withSession([
            '_token' => $csrfToken,
            'internal_chat_console.participant_id' => 'console-test-user-2',
            'internal_chat_console.transcript' => [
                [
                    'actor' => 'user',
                    'type' => 'text',
                    'text' => 'hola',
                ],
                [
                    'actor' => 'bot',
                    'type' => 'menu',
                    'menu' => [
                        'buttons' => [
                            ['id' => 'op_consultas', 'title' => __('whatsapp.menu.button_titles.consultas')],
                            ['id' => 'op_inasistencia', 'title' => __('whatsapp.menu.button_titles.aviso_ausencia')],
                            ['id' => 'op_certificado', 'title' => __('whatsapp.menu.button_titles.anticipo_certificado')],
                        ],
                    ],
                ],
            ],
        ])->post('/internal/chat', [
            '_token' => $csrfToken,
            'button_id' => 'op_certificado',
            'button_title' => __('whatsapp.menu.button_titles.anticipo_certificado'),
        ])
            ->assertRedirect('/internal/chat')
            ->assertSessionHas('internal_chat_console.transcript', function (array $transcript): bool {
                $lastEntry = $transcript[array_key_last($transcript)] ?? null;

                return is_array($lastEntry)
                    && ($lastEntry['actor'] ?? null) === 'bot'
                    && ($lastEntry['text'] ?? null) === __('whatsapp.identificacion.nombre_completo');
            });
    }
}
