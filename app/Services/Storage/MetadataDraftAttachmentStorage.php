<?php

namespace App\Services\Storage;

use App\Services\Storage\Contracts\DraftAttachmentStorage;
use Illuminate\Support\Carbon;

class MetadataDraftAttachmentStorage implements DraftAttachmentStorage
{
    public function store(array $media, ?string $incomingMessageType = null): StoredDraftAttachment
    {
        return new StoredDraftAttachment(
            $media['provider_media_id'] ?? null,
            $media['mime_type'] ?? null,
            $media['filename'] ?? null,
            $media['caption'] ?? null,
            $media['source_type'] ?? $incomingMessageType,
            config('medicina_laboral.storage.draft_driver', config('medicina_laboral.storage.driver', 'metadata')),
            'metadata_only',
            Carbon::now()->toIso8601String(),
        );
    }
}
