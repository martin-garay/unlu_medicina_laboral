<?php

use App\Models\Aviso;
use App\Models\Conversacion;
use App\Services\AnticipoCertificadoService;
use App\Services\Certificates\CertificateAttachmentService;
use App\Services\Certificates\CertificatePolicy;
use App\Services\Certificates\WhatsAppMediaDownloader;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Run only against an isolated disposable database; never against deployment DB.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (getenv('CERTIFICATE_TEST_DB') !== 'certificate_test') {
    throw new RuntimeException('Disposable DB guard missing');
}
config()->set('database.default', 'pgsql');
config()->set('database.connections.pgsql', [
    'driver' => 'pgsql', 'host' => 'certificate-test-db', 'port' => 5432, 'database' => 'certificate_test',
    'username' => 'certificate_test', 'password' => 'synthetic-only', 'charset' => 'utf8', 'prefix' => '', 'schema' => 'public', 'sslmode' => 'prefer',
]);
DB::purge('pgsql');
config()->set('medicina_laboral.storage.driver', 'pgsql');
Artisan::call('migrate', ['--force' => true]);
$policy = app(CertificatePolicy::class)->snapshot();
$c = Conversacion::create(['uuid' => (string) Str::uuid(), 'wa_number' => 'synthetic',
    'canal' => 'whatsapp', 'activa' => true, 'estado' => 'certificado_adjunto',
    'metadata' => ['certificado' => ['intento_uuid' => (string) Str::uuid(), 'politica' => $policy]]]);
$service = app(CertificateAttachmentService::class);
$a = $service->register($c, ['provider_media_id' => 'synthetic-media', 'filename' => 'test.pdf'], 'synthetic-message');
$meta = $c->metadata;
$meta['certificado']['adjuntos'] = [$a];
$c->update(['metadata' => $meta]);
$bytes = "%PDF-1.4\nsynthetic\x00\xff\n";
$app->instance(WhatsAppMediaDownloader::class, new class($bytes) extends WhatsAppMediaDownloader
{
    public function __construct(private string $bytes) {}

    public function download(string $id, int $limit): string
    {
        return $this->bytes;
    }
});
$service->download($a['archivo_id']);
$service->download($a['archivo_id']);
$stored = DB::table('anticipo_certificado_archivo_contenidos')->value('contenido');
if (is_resource($stored)) {
    $stored = stream_get_contents($stored);
}
if ($stored !== $bytes || ! $service->ready($c->refresh())) {
    throw new RuntimeException('bytea roundtrip failed');
}
if (DB::table('jobs')->count() !== 1) {
    throw new RuntimeException('durable queue failed');
}
// Confirm without selecting/reinserting binary content, and reject second association.
$aviso = Aviso::create(['tipo' => 'personal', 'estado' => 'inicial', 'wa_number' => 'synthetic']);
$meta = $c->metadata;
$meta['certificado']['aviso_id'] = $aviso->id;
$meta['certificado']['tipo_certificado'] = 'enfermedad';
$c->update(['metadata' => $meta]);
$anticipo = app(AnticipoCertificadoService::class)->createFromConversation($c);
$repeat = app(AnticipoCertificadoService::class)->createFromConversation($c);
if ($anticipo->id !== $repeat->id || DB::table('anticipo_certificado_archivo_contenidos')->count() !== 1) {
    throw new RuntimeException('confirmation not idempotent');
}
// Consume the actual database job: confirmed file is a safe no-op.
Artisan::call('queue:work', ['connection' => 'database', '--queue' => 'certificates', '--once' => true]);
if (DB::table('jobs')->count() !== 0) {
    throw new RuntimeException('queue worker did not consume job');
}
// Second connection cancels the conversation while downloader holds no SQL lock.
$c2 = Conversacion::create(['uuid' => (string) Str::uuid(), 'wa_number' => 'synthetic2',
    'canal' => 'whatsapp', 'activa' => true, 'estado' => 'certificado_adjunto',
    'metadata' => ['certificado' => ['intento_uuid' => (string) Str::uuid(), 'politica' => $policy]]]);
$b = $service->register($c2, ['provider_media_id' => 'synthetic-media', 'filename' => 'test.pdf'], 'synthetic-message');
$app->instance(WhatsAppMediaDownloader::class, new class($c2->id, $bytes) extends WhatsAppMediaDownloader
{
    public function __construct(private int $id, private string $bytes) {}

    public function download(string $id, int $limit): string
    {
        $other = new PDO('pgsql:host=certificate-test-db;dbname=certificate_test', 'certificate_test', 'synthetic-only');
        $q = $other->prepare('UPDATE conversaciones SET activa=false WHERE id=?');
        $q->execute([$this->id]);

        return $this->bytes;
    }
});
$service->download($b['archivo_id']);
if (DB::table('anticipo_certificado_archivo_contenidos')->where('archivo_id', $b['archivo_id'])->exists()) {
    throw new RuntimeException('late job crossed cancellation');
}
echo "PostgreSQL migration, binary bytea roundtrip, durable queue, confirmation and worker OK\n";
