<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class OperationalDoctorCommandTest extends TestCase
{
    protected function setWhatsAppEnv(string $verifyToken, string $token, string $phoneId): void
    {
        putenv("WHATSAPP_VERIFY_TOKEN={$verifyToken}");
        putenv("WHATSAPP_TOKEN={$token}");
        putenv("WHATSAPP_PHONE_ID={$phoneId}");

        $_ENV['WHATSAPP_VERIFY_TOKEN'] = $verifyToken;
        $_ENV['WHATSAPP_TOKEN'] = $token;
        $_ENV['WHATSAPP_PHONE_ID'] = $phoneId;
        $_SERVER['WHATSAPP_VERIFY_TOKEN'] = $verifyToken;
        $_SERVER['WHATSAPP_TOKEN'] = $token;
        $_SERVER['WHATSAPP_PHONE_ID'] = $phoneId;
    }

    public function test_doctor_reports_ok_with_warnings_when_optional_whatsapp_credentials_are_missing(): void
    {
        config()->set('app.key', 'base64:test-key');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('logging.default', 'stderr');
        config()->set('medicina_laboral.conversation.first_inactivity_minutes', 30);
        config()->set('medicina_laboral.conversation.second_inactivity_minutes', 60);
        config()->set('medicina_laboral.conversation.second_inactivity_action', 'cancel');
        config()->set('medicina_laboral.storage.draft_driver', 'metadata');
        config()->set('medicina_laboral.storage.final_driver', 'metadata');
        config()->set('medicina_laboral.storage.final_attachments.disk', 'local');

        $this->setWhatsAppEnv('test-verify-token', '', '');

        $this->artisan('medicina:doctor')
            ->expectsOutputToContain('[OK] app_key:')
            ->expectsOutputToContain('[OK] database:')
            ->expectsOutputToContain('[OK] logging:')
            ->expectsOutputToContain('[WARN] whatsapp:')
            ->expectsOutputToContain('[OK] timeouts:')
            ->expectsOutputToContain('[OK] storage:')
            ->expectsOutputToContain('Diagnóstico operativo OK con advertencias.')
            ->assertSuccessful();
    }

    public function test_doctor_fails_when_timeout_thresholds_are_invalid(): void
    {
        config()->set('app.key', 'base64:test-key');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('logging.default', 'stderr');
        config()->set('medicina_laboral.conversation.first_inactivity_minutes', 60);
        config()->set('medicina_laboral.conversation.second_inactivity_minutes', 30);
        config()->set('medicina_laboral.conversation.second_inactivity_action', 'cancel');
        config()->set('medicina_laboral.storage.draft_driver', 'metadata');
        config()->set('medicina_laboral.storage.final_driver', 'metadata');
        config()->set('medicina_laboral.storage.final_attachments.disk', 'local');

        $this->setWhatsAppEnv('test-verify-token', '', '');

        $this->artisan('medicina:doctor')
            ->expectsOutputToContain('[ERROR] timeouts:')
            ->expectsOutputToContain('Diagnóstico operativo fallido.')
            ->assertFailed();
    }
}
