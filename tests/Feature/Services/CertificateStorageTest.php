<?php

namespace Tests\Feature\Services;

use App\Models\AnticipoCertificado;
use App\Models\AnticipoCertificadoArchivo;
use App\Models\Aviso;
use App\Models\Conversacion;
use App\Models\User;
use App\Services\Certificates\CertificateAttachmentService;
use App\Services\Certificates\CertificatePolicy;
use App\Services\Certificates\WhatsAppMediaDownloader;
use Database\Seeders\BackofficeRolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class CertificateStorageTest extends TestCase
{
    use CreatesTestingSchema;

    private function conversation(): Conversacion
    {
        return Conversacion::create(['uuid' => (string) Str::uuid(), 'wa_number' => (string) random_int(100000, 999999),
            'activa' => true, 'canal' => 'whatsapp', 'estado' => 'certificado_adjunto', 'paso_actual' => 'certificado_adjunto',
            'metadata' => ['certificado' => ['intento_uuid' => (string) Str::uuid(), 'politica' => app(CertificatePolicy::class)->snapshot()]]]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
        (require database_path('migrations/2026_10_02_000010_add_certificate_content_and_queue.php'))->up();
        config()->set('medicina_laboral.storage.driver', 'pgsql');
    }

    private function receive(Conversacion $c, string $message = 'message-1'): array
    {
        $a = app(CertificateAttachmentService::class)->register($c, ['provider_media_id' => 'media-1', 'filename' => 'cert.pdf', 'mime_type' => 'application/pdf'], $message);
        $meta = $c->metadata;
        $meta['certificado']['adjuntos'][] = $a;
        $c->update(['metadata' => $meta]);

        return $a;
    }

    public function test_receipt_publishes_durable_job_and_deduplicates_per_origin(): void
    {
        $c = $this->conversation();
        $a = $this->receive($c);
        $b = app(CertificateAttachmentService::class)->register($c, ['provider_media_id' => 'media-1'], 'message-1');
        $this->assertSame($a['archivo_id'], $b['archivo_id']);
        $this->assertDatabaseCount('jobs', 1);
        $payload = DB::table('jobs')->value('payload');
        $this->assertStringNotContainsString('media-1', $payload);
    }

    public function test_two_conversations_never_share_records_even_for_same_media(): void
    {
        $a = $this->receive($this->conversation());
        $b = $this->receive($this->conversation());
        $this->assertNotSame($a['archivo_id'], $b['archivo_id']);
        $this->assertDatabaseCount('anticipo_certificado_archivos', 2);
    }

    public function test_job_stores_bytes_and_duplicate_job_is_idempotent(): void
    {
        $c = $this->conversation();
        $a = $this->receive($c);
        $this->mock(WhatsAppMediaDownloader::class)->shouldReceive('download')->once()->andReturn('%PDF-1.4 synthetic');
        $s = app(CertificateAttachmentService::class);
        $s->download($a['archivo_id']);
        $s->download($a['archivo_id']);
        $this->assertDatabaseCount('anticipo_certificado_archivo_contenidos', 1);
        $this->assertSame('%PDF-1.4 synthetic', DB::table('anticipo_certificado_archivo_contenidos')->value('contenido'));
        $this->assertTrue($s->ready($c->refresh()));
    }

    public function test_late_job_for_replaced_attempt_cannot_store_bytes(): void
    {
        $c = $this->conversation();
        $a = $this->receive($c);
        $meta = $c->metadata;
        $meta['certificado']['intento_uuid'] = (string) Str::uuid();
        $c->update(['metadata' => $meta]);
        $this->mock(WhatsAppMediaDownloader::class)->shouldNotReceive('download');
        app(CertificateAttachmentService::class)->download($a['archivo_id']);
        $this->assertDatabaseCount('anticipo_certificado_archivo_contenidos', 0);
        $this->assertDatabaseHas('anticipo_certificado_archivos', ['id' => $a['archivo_id'], 'estado_storage' => 'descartado']);
    }

    public function test_cancellation_during_network_wait_discards_result(): void
    {
        $c = $this->conversation();
        $a = $this->receive($c);
        $this->mock(WhatsAppMediaDownloader::class)->shouldReceive('download')->once()->andReturnUsing(function () use ($c) {
            $c->update(['activa' => false]);

            return '%PDF-1.4 synthetic';
        });
        app(CertificateAttachmentService::class)->download($a['archivo_id']);
        $this->assertDatabaseCount('anticipo_certificado_archivo_contenidos', 0);
    }

    public function test_foreign_attachment_cannot_be_confirmed(): void
    {
        $c = $this->conversation();
        $other = $this->conversation();
        $a = $this->receive($other);
        $this->mock(WhatsAppMediaDownloader::class)->shouldReceive('download')->andReturn('%PDF-1.4 synthetic');
        $s = app(CertificateAttachmentService::class);
        $s->download($a['archivo_id']);
        $meta = $c->metadata;
        $meta['certificado']['adjuntos'] = [$a];
        $c->update(['metadata' => $meta]);
        $this->assertFalse($s->ready($c));
        $this->expectException(ModelNotFoundException::class);
        $s->associate($a, $c, 999);
    }

    public function test_policy_enforces_real_mime_and_exact_size_boundary(): void
    {
        $bytes = '%PDF-1.4 synthetic';
        $p = app(CertificatePolicy::class)->snapshot();
        $p['max_bytes'] = strlen($bytes);
        $this->assertSame('application/pdf', app(CertificatePolicy::class)->validate($bytes, $p, 'a.pdf'));
        $this->expectException(\RuntimeException::class);
        app(CertificatePolicy::class)->validate($bytes.'x', $p, 'a.pdf');
    }

    public function test_downloader_uses_proxy_and_rejects_untrusted_media_host(): void
    {
        config()->set('medicina_laboral.whatsapp.token', 'fake-token');
        config()->set('medicina_laboral.whatsapp.proxy_unlu', 'http://proxy.unlu.edu.ar:8080');
        Http::fake(function ($request, $options) {
            $this->assertSame('http://proxy.unlu.edu.ar:8080', $options['proxy']);
            $this->assertFalse($options['allow_redirects']);

            return Http::response(['url' => 'https://evil.example/media'], 200);
        });
        $this->expectException(\RuntimeException::class);
        app(WhatsAppMediaDownloader::class)->download('media', 1024);
    }

    private function authorizedUser(string $role): User
    {
        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2026_04_28_173621_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_04_28_230300_create_auditoria_administrativa_table.php'))->up();
        config()->set('backoffice.local_admin.enabled', false);
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);
        $u = User::create(['name' => 'Test', 'email' => 'test@example.test', 'password' => 'secret']);
        $u->assignRole($role);

        return $u;
    }

    private function finalAttachment(): array
    {
        $c = $this->conversation();
        $a = $this->receive($c);
        $this->mock(WhatsAppMediaDownloader::class)->shouldReceive('download')->andReturn('%PDF-1.4 synthetic');
        app(CertificateAttachmentService::class)->download($a['archivo_id']);
        $aviso = Aviso::create(['uuid' => (string) Str::uuid(), 'estado' => 'inicial', 'tipo' => 'personal']);
        $anticipo = AnticipoCertificado::create(['uuid' => (string) Str::uuid(), 'conversacion_id' => $c->id,
            'aviso_id' => $aviso->id, 'tipo_certificado' => 'enfermedad', 'estado' => 'inicial', 'metadata' => $c->metadata]);
        app(CertificateAttachmentService::class)->associate($a, $c, $anticipo->id);

        return [$anticipo, AnticipoCertificadoArchivo::find($a['archivo_id'])];
    }

    public function test_admin_download_is_authenticated_authorized_and_audited(): void
    {
        [$anticipo,$file] = $this->finalAttachment();
        $u = $this->authorizedUser('admin');
        $url = route('certificates.download', ['anticipo' => $anticipo->id, 'archivo' => $file->id]);
        $this->get($url)->assertRedirect();
        $this->actingAs($u)->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertContent('%PDF-1.4 synthetic');
        $this->assertDatabaseHas('auditoria_administrativa', ['action' => 'certificate.downloaded', 'actor_user_id' => $u->id]);
    }

    public function test_auditor_cannot_download_even_with_view_permission(): void
    {
        [$anticipo,$file] = $this->finalAttachment();
        $u = $this->authorizedUser('auditor');
        $this->actingAs($u)->get(route('certificates.download', ['anticipo' => $anticipo->id, 'archivo' => $file->id]))->assertForbidden();
        $this->assertDatabaseMissing('auditoria_administrativa', ['action' => 'certificate.downloaded']);
    }

    public function test_admin_cannot_download_file_through_wrong_anticipo(): void
    {
        [$anticipo,$file] = $this->finalAttachment();
        $u = $this->authorizedUser('admin');
        $other = AnticipoCertificado::create(['uuid' => (string) Str::uuid(), 'conversacion_id' => $anticipo->conversacion_id,
            'aviso_id' => $anticipo->aviso_id, 'tipo_certificado' => 'enfermedad']);
        $this->actingAs($u)->get(route('certificates.download', ['anticipo' => $other->id, 'archivo' => $file->id]))->assertNotFound();
    }

    public function test_downloader_fetches_media_with_bounded_sink_and_verified_tls(): void
    {
        config()->set('medicina_laboral.whatsapp.token', 'fake-token');
        config()->set('medicina_laboral.whatsapp.proxy_unlu', 'http://proxy.unlu.edu.ar:8080');
        Http::fake(function ($request, $options) {
            $this->assertTrue($options['verify']);
            if (str_contains($request->url(), 'graph.facebook.com')) {
                return Http::response(['url' => 'https://lookaside.fbsbx.com/media', 'file_size' => 20]);
            }
            fwrite($options['sink'], '%PDF-1.4 synthetic');

            return Http::response('');
        });
        $this->assertSame('%PDF-1.4 synthetic', app(WhatsAppMediaDownloader::class)->download('media', 1024));
        Http::assertSentCount(2);
    }

    public function test_failed_job_records_safe_reason_and_event(): void
    {
        $c = $this->conversation();
        $a = $this->receive($c);
        app(CertificateAttachmentService::class)->fail($a['archivo_id'], 'invalid_size');
        $this->assertDatabaseHas('anticipo_certificado_archivos', ['id' => $a['archivo_id'], 'estado_storage' => 'fallido', 'motivo_rechazo' => 'invalid_size']);
        $this->assertDatabaseHas('conversacion_eventos', ['conversacion_id' => $c->id, 'tipo_evento' => 'certificate_download_failed']);
    }
}
