<?php

namespace App\Services\Storage;

use App\Models\AnticipoCertificado;
use App\Models\Conversacion;
use App\Services\Storage\Contracts\FinalAttachmentStorage;
use Illuminate\Support\Str;

class MetadataFinalAttachmentStorage implements FinalAttachmentStorage
{
    public function persist(array $attachment, Conversacion $conversation, AnticipoCertificado $anticipo): StoredFinalAttachment
    {
        $disk = (string) config('medicina_laboral.storage.final_attachments.disk', 'local');
        $directory = trim((string) config('medicina_laboral.storage.final_attachments.directory', 'medicina_laboral/anticipos'), '/');
        $basename = $attachment['provider_media_id']
            ?? pathinfo((string) ($attachment['filename'] ?? ''), PATHINFO_FILENAME)
            ?: (string) Str::uuid();
        $extension = $this->resolveExtension($attachment);
        $relativePath = $directory . '/anticipo-' . $anticipo->id . '/' . $basename . ($extension ? '.' . $extension : '');

        return new StoredFinalAttachment(
            providerFileId: $attachment['provider_media_id'] ?? null,
            originalName: $attachment['filename'] ?? null,
            mimeType: $attachment['mime_type'] ?? null,
            extension: $extension,
            sizeBytes: isset($attachment['size_bytes']) ? (int) $attachment['size_bytes'] : null,
            storageDisk: $disk,
            storagePath: $relativePath,
            fileHash: $attachment['sha256'] ?? null,
            validationStatus: 'aceptado',
            rejectionReason: null,
            metadata: [
                'caption' => $attachment['caption'] ?? null,
                'source_type' => $attachment['source_type'] ?? null,
                'storage_driver' => config('medicina_laboral.storage.final_driver', config('medicina_laboral.storage.driver', 'metadata')),
                'storage_status' => 'metadata_only',
                'stored_at' => $attachment['stored_at'] ?? now()->toIso8601String(),
                'conversation_id' => $conversation->id,
                'anticipo_certificado_id' => $anticipo->id,
            ],
        );
    }

    private function resolveExtension(array $attachment): ?string
    {
        if (!empty($attachment['extension'])) {
            return mb_strtolower((string) $attachment['extension']);
        }

        $filename = (string) ($attachment['filename'] ?? '');
        $fromName = pathinfo($filename, PATHINFO_EXTENSION);

        if ($fromName !== '') {
            return mb_strtolower($fromName);
        }

        return match ($attachment['mime_type'] ?? null) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };
    }
}
