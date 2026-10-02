<?php

namespace Tests\Feature\Services;

use App\Services\WhatsAppSender;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppSenderTest extends TestCase
{
    public function test_text_and_interactive_messages_use_configured_proxy(): void
    {
        config()->set('medicina_laboral.whatsapp.proxy_unlu', 'http://proxy.unlu.edu.ar:8080');
        $optionsSeen = [];
        Http::fake(function ($request, $options) use (&$optionsSeen) {
            $optionsSeen[] = $options;

            return Http::response([], 200);
        });

        $sender = new WhatsAppSender('test-token', 'test-phone');
        $sender->sendText('541111111111', 'Prueba');
        $sender->sendInteractiveMenu('541111111111', [
            'body_text' => 'Menú',
            'buttons' => [['id' => 'test', 'title' => 'Prueba']],
        ]);

        Http::assertSentCount(2);
        foreach ($optionsSeen as $options) {
            $this->assertSame('http://proxy.unlu.edu.ar:8080', $options['proxy']);
            $this->assertTrue($options['verify']);
            $this->assertSame(10, $options['timeout']);
        }
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v21.0/test-phone/messages'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['type'] === 'text');
    }

    public function test_empty_proxy_preserves_default_http_options(): void
    {
        config()->set('medicina_laboral.whatsapp.proxy_unlu', '');
        $optionsSeen = null;
        Http::fake(function ($request, $options) use (&$optionsSeen) {
            $optionsSeen = $options;

            return Http::response([], 200);
        });

        (new WhatsAppSender('test-token', 'test-phone'))->sendText('541111111111', 'Prueba');

        Http::assertSentCount(1);
        $this->assertNotNull($optionsSeen);
        $this->assertNotSame('http://proxy.unlu.edu.ar:8080', $optionsSeen['proxy'] ?? null);
    }
}
