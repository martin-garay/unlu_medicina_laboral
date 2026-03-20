<?php

namespace App\Services\Storage;

class StoredFinalAttachment
{
    public function __construct(
        public readonly ?string $providerFileId,
        public readonly ?string $originalName,
        public readonly ?string $mimeType,
        public readonly ?string $extension,
        public readonly ?int $sizeBytes,
        public readonly ?string $storageDisk,
        public readonly ?string $storagePath,
        public readonly ?string $fileHash,
        public readonly string $validationStatus,
        public readonly ?string $rejectionReason,
        public readonly array $metadata,
    ) {
    }

    public function toArray(): array
    {
        return [
            'provider_file_id' => $this->providerFileId,
            'nombre_original' => $this->originalName,
            'mime_type' => $this->mimeType,
            'extension' => $this->extension,
            'size_bytes' => $this->sizeBytes,
            'storage_disk' => $this->storageDisk,
            'storage_path' => $this->storagePath,
            'hash_archivo' => $this->fileHash,
            'estado_validacion' => $this->validationStatus,
            'motivo_rechazo' => $this->rejectionReason,
            'metadata' => $this->metadata,
        ];
    }
}
