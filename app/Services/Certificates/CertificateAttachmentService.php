<?php

namespace App\Services\Certificates;

use App\Jobs\DownloadCertificate;
use App\Models\AnticipoCertificado;
use App\Models\AnticipoCertificadoArchivo;
use App\Models\Conversacion;
use App\Services\ConversationEventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class CertificateAttachmentService
{
    public function enabled(): bool
    {
        return config('medicina_laboral.storage.driver') === 'pgsql';
    }

    public function register(Conversacion $conversation, array $media, string $messageId): array
    {
        return DB::transaction(function () use ($conversation, $media, $messageId) {
            $c = Conversacion::query()->lockForUpdate()->findOrFail($conversation->id);
            $attempt = data_get($c->metadata, 'certificado.intento_uuid');
            $policy = data_get($c->metadata, 'certificado.politica');
            if (! $c->activa || ! $attempt || ! $policy || ! $messageId || empty($media['provider_media_id'])) {
                throw new \RuntimeException('invalid_attachment_context');
            }
            $query = AnticipoCertificadoArchivo::query()->where('conversacion_id', $c->id)->where('intento_uuid', $attempt);
            $file = (clone $query)->where('provider_message_id', $messageId)->first();
            if (! $file) {
                if ($query->count() >= $policy['max_files']) {
                    throw new \RuntimeException('attachment_limit');
                }
                $file = AnticipoCertificadoArchivo::create([
                    'uuid' => (string) Str::uuid(), 'conversacion_id' => $c->id, 'intento_uuid' => $attempt,
                    'provider_message_id' => $messageId, 'provider_file_id' => $media['provider_media_id'],
                    'nombre_original' => $media['filename'] ?? null, 'mime_type' => $media['mime_type'] ?? null,
                    'estado_storage' => 'pendiente', 'estado_validacion' => 'pendiente', 'politica' => $policy,
                ]);
                // Queue publication and metadata commit on the same DB transaction.
                Queue::connection('database')->push(new DownloadCertificate($file->id), '', 'certificates');
            }

            return ['archivo_id' => $file->id, 'provider_media_id' => $file->provider_file_id,
                'filename' => $file->nombre_original, 'mime_type' => $file->mime_type,
                'storage_status' => $file->estado_storage, 'intento_uuid' => $attempt];
        });
    }

    public function current(Conversacion $c, AnticipoCertificadoArchivo $file): bool
    {
        return $c->activa && $file->conversacion_id === $c->id
            && $file->intento_uuid === data_get($c->metadata, 'certificado.intento_uuid');
    }

    public function download(int $id): void
    {
        $file = AnticipoCertificadoArchivo::findOrFail($id);
        if ($file->estado_storage !== 'pendiente') {
            return;
        }
        $c = Conversacion::findOrFail($file->conversacion_id);
        if (! $this->current($c, $file)) {
            $this->discard($id);

            return;
        }
        try {
            $bytes = app(WhatsAppMediaDownloader::class)->download($file->provider_file_id, $file->politica['max_bytes']);
            $mime = app(CertificatePolicy::class)->validate($bytes, $file->politica, $file->nombre_original);
        } catch (\Throwable $e) {
            // Never put credential-bearing provider URLs or response payloads in queue errors.
            throw new \RuntimeException(in_array($e->getMessage(), ['invalid_size', 'invalid_mime', 'invalid_extension', 'invalid_media_url', 'missing_credentials'], true) ? $e->getMessage() : 'certificate_download_failed');
        }
        DB::transaction(function () use ($file, $bytes, $mime) {
            $c = Conversacion::query()->lockForUpdate()->findOrFail($file->conversacion_id);
            $f = AnticipoCertificadoArchivo::query()->lockForUpdate()->findOrFail($file->id);
            if ($f->estado_storage !== 'pendiente') {
                return;
            }
            if (! $this->current($c, $f)) {
                $f->update(['estado_storage' => 'descartado']);

                return;
            }
            if (DB::getDriverName() === 'pgsql') {
                $pdo = DB::connection()->getPdo();
                $statement = $pdo->prepare('INSERT INTO anticipo_certificado_archivo_contenidos (archivo_id, contenido) VALUES (?, ?)');
                $statement->bindValue(1, $f->id, \PDO::PARAM_INT);
                $statement->bindValue(2, $bytes, \PDO::PARAM_LOB);
                $statement->execute();
            } else {
                DB::table('anticipo_certificado_archivo_contenidos')->insert(['archivo_id' => $f->id, 'contenido' => $bytes]);
            }
            $f->update(['estado_storage' => 'disponible', 'estado_validacion' => 'aceptado',
                'size_bytes' => strlen($bytes), 'hash_archivo' => hash('sha256', $bytes), 'mime_type' => $mime,
                'storage_disk' => 'pgsql', 'extension' => match ($mime) {
                    'application/pdf' => 'pdf','image/jpeg' => 'jpg','image/png' => 'png'
                }]);
            app(ConversationEventService::class)->record($c, 'certificate_downloaded', ['metadata' => ['archivo_id' => $f->id]]);
        });
    }

    public function discard(int $id): void
    {
        AnticipoCertificadoArchivo::whereKey($id)->where('estado_storage', 'pendiente')->update(['estado_storage' => 'descartado']);
    }

    public function fail(int $id, ?string $reason = null): void
    {
        $reason = in_array($reason, ['invalid_size', 'invalid_mime', 'invalid_extension', 'invalid_media_url', 'missing_credentials'], true) ? $reason : 'download_failed';
        DB::transaction(function () use ($id, $reason) {
            $file = AnticipoCertificadoArchivo::find($id);
            if (! $file) {
                return;
            }
            $c = Conversacion::query()->lockForUpdate()->findOrFail($file->conversacion_id);
            $f = AnticipoCertificadoArchivo::query()->lockForUpdate()->findOrFail($id);
            if ($f->estado_storage !== 'pendiente') {
                return;
            }
            $f->update(['estado_storage' => $this->current($c, $f) ? 'fallido' : 'descartado',
                'estado_validacion' => 'rechazado', 'motivo_rechazo' => $reason]);
            app(ConversationEventService::class)->record($c, 'certificate_download_failed',
                ['metadata' => ['archivo_id' => $id, 'reason' => $reason]]);
        });
    }

    public function ready(Conversacion $c): bool
    {
        $attachments = data_get($c->metadata, 'certificado.adjuntos', []);
        if (! $attachments) {
            return false;
        }
        foreach ($attachments as $a) {
            $file = AnticipoCertificadoArchivo::find($a['archivo_id'] ?? null);
            if (! $file || ! $this->current($c, $file) || $file->estado_storage !== 'disponible'
                || ! DB::table('anticipo_certificado_archivo_contenidos')->where('archivo_id', $file->id)->exists()) {
                return false;
            }
        }

        return true;
    }

    public function associate(array $attachment, Conversacion $c, int $anticipoId): void
    {
        DB::transaction(function () use ($attachment, $c, $anticipoId) {
            $c = Conversacion::query()->lockForUpdate()->findOrFail($c->id);
            $anticipo = AnticipoCertificado::findOrFail($anticipoId);
            $f = AnticipoCertificadoArchivo::query()->lockForUpdate()->findOrFail($attachment['archivo_id']);
            if ($anticipo->conversacion_id !== $c->id ||
                data_get($anticipo->metadata, 'certificado.intento_uuid') !== $f->intento_uuid ||
                ! $this->current($c, $f) || $f->estado_storage !== 'disponible' ||
                ! DB::table('anticipo_certificado_archivo_contenidos')->where('archivo_id', $f->id)->exists()) {
                throw new \RuntimeException('attachment_not_available');
            }
            $f->update(['anticipo_certificado_id' => $anticipoId, 'estado_storage' => 'confirmado']);
        });
    }
}
