<?php

namespace App\Services\Storage;

class StoredDraftAttachment
{
    public function __construct(
        public readonly ?string $providerMediaId,
        public readonly ?string $mimeType,
        public readonly ?string $filename,
        public readonly ?string $caption,
        public readonly ?string $sourceType,
        public readonly string $storageDriver,
        public readonly string $storageStatus,
        public readonly string $storedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'provider_media_id' => $this->providerMediaId,
            'mime_type' => $this->mimeType,
            'filename' => $this->filename,
            'caption' => $this->caption,
            'source_type' => $this->sourceType,
            'storage_driver' => $this->storageDriver,
            'storage_status' => $this->storageStatus,
            'stored_at' => $this->storedAt,
        ];
    }
}
