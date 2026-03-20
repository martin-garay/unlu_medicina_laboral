<?php

namespace App\Services\Storage\Contracts;

use App\Models\AnticipoCertificado;
use App\Models\Conversacion;
use App\Services\Storage\StoredFinalAttachment;

interface FinalAttachmentStorage
{
    public function persist(array $attachment, Conversacion $conversation, AnticipoCertificado $anticipo): StoredFinalAttachment;
}
