<?php

namespace App\Services\Storage\Contracts;

use App\Services\Storage\StoredDraftAttachment;

interface DraftAttachmentStorage
{
    public function store(array $media, ?string $incomingMessageType = null): StoredDraftAttachment;
}
